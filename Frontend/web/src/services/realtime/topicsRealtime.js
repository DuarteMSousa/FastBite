import { getEchoClient } from './echoClient'

function createClientMessageId() {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID()
  }

  const bytes = new Uint8Array(16)
  if (globalThis.crypto?.getRandomValues) {
    globalThis.crypto.getRandomValues(bytes)
  } else {
    for (let index = 0; index < bytes.length; index += 1) {
      bytes[index] = Math.floor(Math.random() * 256)
    }
  }

  bytes[6] = (bytes[6] & 0x0f) | 0x40
  bytes[8] = (bytes[8] & 0x3f) | 0x80

  const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0'))
  return `${hex.slice(0, 4).join('')}-${hex.slice(4, 6).join('')}-${hex
    .slice(6, 8)
    .join('')}-${hex.slice(8, 10).join('')}-${hex.slice(10, 16).join('')}`
}

function listenMany(channel, events, callback) {
  events.forEach((eventName) => {
    channel.listen(`.${eventName}`, (payload) => {
      callback(eventName, payload)
    })
  })
}

export function subscribeToChatTopic({ chatId, authToken, devUserId, onMessage, onError, onSubscribed }) {
  const echo = getEchoClient({ authToken, devUserId })
  const channelName = `chat.${chatId}`
  const channel = echo.private(channelName)

  if (onSubscribed && typeof channel.subscribed === 'function') {
    channel.subscribed(() => onSubscribed())
  }

  channel.listen('.CHAT_MESSAGE_SENT', (payload) => {
    if (onMessage) onMessage(payload)
  })

  channel.error((error) => {
    if (onError) onError(error)
  })

  return () => echo.leave(channelName)
}

export async function sendChatMessageOverSocket({ chatId, content, authToken, devUserId }) {
  const trimmed = String(content ?? '').trim()
  if (!trimmed) {
    throw new Error('A mensagem não pode estar vazia.')
  }

  const echo = getEchoClient({ authToken, devUserId })
  const clientMessageId = createClientMessageId()
  const ack = await echo.send(
    {
      type: 'chat.message.send',
      chat_id: chatId,
      content: trimmed,
      client_message_id: clientMessageId,
    },
    {
      ackType: 'chat.message.send.ack',
      matcher: (message) =>
        message.client_message_id === clientMessageId ||
        (!message.client_message_id && message.chat_id === chatId),
    },
  )

  return {
    id: ack?.message_id ?? clientMessageId,
    chat_id: ack?.chat_id ?? chatId,
    client_message_id: clientMessageId,
  }
}

export function subscribeToUserNotificationsTopic({
  userId,
  authToken,
  devUserId,
  onNotification,
  onError,
  onSubscribed,
}) {
  const echo = getEchoClient({ authToken, devUserId })
  const channelName = `user.${userId}.notifications`
  const channel = echo.private(channelName)

  if (onSubscribed && typeof channel.subscribed === 'function') {
    channel.subscribed(() => onSubscribed())
  }

  channel.listen('.USER_NOTIFICATION_CREATED', (payload) => {
    if (onNotification) onNotification(payload)
  })

  channel.error((error) => {
    if (onError) onError(error)
  })

  return () => echo.leave(channelName)
}

export function subscribeToRestaurantOrdersTopic({
  restaurantId,
  authToken,
  devUserId,
  onEvent,
  onError,
  onSubscribed,
}) {
  const echo = getEchoClient({ authToken, devUserId })
  const channelName = `restaurant.${restaurantId}.orders`
  const channel = echo.private(channelName)

  if (onSubscribed && typeof channel.subscribed === 'function') {
    channel.subscribed(() => onSubscribed())
  }

  const eventNames = [
    'ORDER_STATUS_UPDATED',
    'ORDER_CREATED',
    'ORDER_PAYMENT_COMPLETED',
    'ORDER_CONFIRMED',
    'ORDER_REJECTED',
    'ORDER_PREPARING',
    'ORDER_READY',
    'ORDER_COURIER_ASSIGNED',
    'ORDER_PICKED_UP',
    'ORDER_OUT_FOR_DELIVERY',
    'ORDER_DELIVERED',
    'ORDER_CANCELLED',
  ]

  eventNames.forEach((eventName) => {
    channel.listen(`.${eventName}`, (payload) => {
      if (onEvent) onEvent(eventName, payload)
    })
  })

  channel.error((error) => {
    if (onError) onError(error)
  })

  return () => echo.leave(channelName)
}

export function subscribeToOrderTrackingTopic({
  orderId,
  authToken,
  devUserId,
  onEvent,
  onPositionUpdated,
  onError,
  onSubscribed,
}) {
  const echo = getEchoClient({ authToken, devUserId })
  const channelName = `order.${orderId}.tracking`
  const channel = echo.private(channelName)

  if (onSubscribed && typeof channel.subscribed === 'function') {
    channel.subscribed(() => onSubscribed())
  }

  listenMany(
    channel,
    [
      'COURIER_POSITION_UPDATED',
      'ORDER_STATUS_UPDATED',
      'ORDER_COURIER_ASSIGNED',
      'ORDER_PREPARING',
      'ORDER_READY',
      'ORDER_PICKED_UP',
      'ORDER_OUT_FOR_DELIVERY',
      'ORDER_DELIVERED',
      'ORDER_CANCELLED',
      'DELIVERY_PICKED_UP',
      'DELIVERY_IN_TRANSIT',
      'DELIVERY_DELIVERED',
      'DELIVERY_FAILED',
    ],
    (eventName, payload) => {
      if (eventName === 'COURIER_POSITION_UPDATED' && onPositionUpdated) {
        onPositionUpdated(payload)
      }
      if (onEvent) onEvent(eventName, payload)
    },
  )

  channel.error((error) => {
    if (onError) onError(error)
  })

  return () => echo.leave(channelName)
}
