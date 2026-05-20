const GATEWAY_URL = import.meta.env.VITE_GATEWAY_WORKER_URL ?? ''
const GATEWAY_HOST = import.meta.env.VITE_GATEWAY_WORKER_HOST ?? '127.0.0.1'
const GATEWAY_PORT = Number(import.meta.env.VITE_GATEWAY_WORKER_PORT ?? 8090)
const GATEWAY_SCHEME = import.meta.env.VITE_GATEWAY_WORKER_SCHEME ?? 'ws'
const DEV_BROADCAST_USER_ID = import.meta.env.VITE_DEV_BROADCAST_USER_ID ?? ''

let gatewaySocket = null
let gatewayClient = null
let currentUserId = null
let reconnectTimer = null
let reconnectAttempts = 0
const pendingMessages = []
const channelStates = new Map()

function normalizeEventName(eventName) {
  return String(eventName ?? '').replace(/^\./, '')
}

function safeCall(callback, ...args) {
  try {
    callback(...args)
  } catch {
    // ignore callback errors
  }
}

function resolveGatewayUrl() {
  if (GATEWAY_URL) {
    return GATEWAY_URL
  }

  const scheme = GATEWAY_SCHEME || 'ws'
  return `${scheme}://${GATEWAY_HOST}:${GATEWAY_PORT}`
}

function notifyChannelError(channelState, error) {
  channelState.errorHandlers.forEach((callback) => safeCall(callback, error))
}

function notifyAllErrors(error) {
  channelStates.forEach((channelState) => notifyChannelError(channelState, error))
}

function markAllChannelsUnsubscribed() {
  channelStates.forEach((channelState) => {
    channelState.subscribed = false
  })
}

function sendOrQueue(message) {
  const encodedMessage = JSON.stringify(message)

  if (gatewaySocket && gatewaySocket.readyState === WebSocket.OPEN) {
    gatewaySocket.send(encodedMessage)
    return
  }

  pendingMessages.push(encodedMessage)
}

function flushPendingMessages() {
  if (!gatewaySocket || gatewaySocket.readyState !== WebSocket.OPEN) {
    return
  }

  while (pendingMessages.length > 0) {
    const message = pendingMessages.shift()
    gatewaySocket.send(message)
  }
}

function subscribeChannel(channelName) {
  sendOrQueue({
    type: 'subscribe',
    channel: channelName,
  })
}

function unsubscribeChannel(channelName) {
  sendOrQueue({
    type: 'unsubscribe',
    channel: channelName,
  })
}

function emitGatewayEvent(message) {
  const channelName = typeof message.channel === 'string' ? message.channel : ''
  const channelState = channelStates.get(channelName)

  if (!channelState) {
    return
  }

  const eventName = normalizeEventName(message.eventName || message.type)
  const payload = message.payload ?? {}
  const listeners = channelState.listeners.get(eventName)

  if (!listeners) {
    return
  }

  listeners.forEach((callback) => safeCall(callback, payload))
}

function handleGatewayMessage(rawData) {
  let message

  try {
    message = JSON.parse(rawData)
  } catch {
    return
  }

  const type = String(message?.type ?? '')

  if (!type) {
    return
  }

  if (type === 'subscribe.ack') {
    const channelName = String(message.channel ?? '')
    const channelState = channelStates.get(channelName)

    if (!channelState) {
      return
    }

    channelState.subscribed = true
    channelState.subscribedHandlers.forEach((callback) => safeCall(callback))
    return
  }

  if (type === 'unsubscribe.ack') {
    const channelName = String(message.channel ?? '')
    const channelState = channelStates.get(channelName)

    if (channelState) {
      channelState.subscribed = false
    }
    return
  }

  if (type === 'gateway.error') {
    const error = new Error(String(message.message ?? 'Gateway error.'))
    const channelName = typeof message.channel === 'string' ? message.channel : ''
    const channelState = channelStates.get(channelName)

    if (channelState) {
      notifyChannelError(channelState, error)
      return
    }

    notifyAllErrors(error)
    return
  }

  emitGatewayEvent(message)
}

function scheduleReconnect() {
  if (reconnectTimer || !currentUserId || channelStates.size === 0) {
    return
  }

  const waitMs = Math.min(1000 * 2 ** reconnectAttempts, 10000)
  reconnectTimer = setTimeout(() => {
    reconnectTimer = null
    reconnectAttempts += 1
    connectGatewaySocket()
  }, waitMs)
}

function connectGatewaySocket() {
  if (!currentUserId) {
    return
  }

  if (
    gatewaySocket &&
    (gatewaySocket.readyState === WebSocket.OPEN || gatewaySocket.readyState === WebSocket.CONNECTING)
  ) {
    return
  }

  gatewaySocket = new WebSocket(resolveGatewayUrl())

  gatewaySocket.onopen = () => {
    reconnectAttempts = 0
    sendOrQueue({
      type: 'hello',
      user_id: currentUserId,
      courier_id: currentUserId,
    })

    channelStates.forEach((_channelState, channelName) => {
      subscribeChannel(channelName)
    })

    flushPendingMessages()
  }

  gatewaySocket.onmessage = (event) => {
    handleGatewayMessage(event.data)
  }

  gatewaySocket.onerror = () => {
    notifyAllErrors(new Error('Gateway connection error.'))
  }

  gatewaySocket.onclose = () => {
    gatewaySocket = null
    markAllChannelsUnsubscribed()
    notifyAllErrors(new Error('Gateway connection closed.'))
    scheduleReconnect()
  }
}

function ensureConnection(devUserId) {
  const resolvedUserId = String(devUserId || DEV_BROADCAST_USER_ID || '').trim()

  if (!resolvedUserId) {
    throw new Error('Missing user id for GatewayWorker hello message.')
  }

  if (currentUserId && currentUserId !== resolvedUserId) {
    disconnectEchoClient()
  }

  if (!currentUserId) {
    currentUserId = resolvedUserId
  }

  connectGatewaySocket()
}

function createChannelApi(channelName) {
  return {
    listen(eventName, callback) {
      const normalizedEventName = normalizeEventName(eventName)
      const channelState = channelStates.get(channelName)
      if (!channelState) return this

      if (!channelState.listeners.has(normalizedEventName)) {
        channelState.listeners.set(normalizedEventName, new Set())
      }

      channelState.listeners.get(normalizedEventName).add(callback)
      return this
    },
    error(callback) {
      const channelState = channelStates.get(channelName)
      if (!channelState) return this
      channelState.errorHandlers.add(callback)
      return this
    },
    subscribed(callback) {
      const channelState = channelStates.get(channelName)
      if (!channelState) return this
      channelState.subscribedHandlers.add(callback)
      if (channelState.subscribed) {
        safeCall(callback)
      }
      return this
    },
  }
}

export function getEchoClient({ devUserId } = {}) {
  ensureConnection(devUserId)

  if (!gatewayClient) {
    gatewayClient = {
      private(channelName) {
        if (!channelStates.has(channelName)) {
          channelStates.set(channelName, {
            listeners: new Map(),
            errorHandlers: new Set(),
            subscribedHandlers: new Set(),
            subscribed: false,
          })
        }

        subscribeChannel(channelName)
        return createChannelApi(channelName)
      },
      leave(channelName) {
        if (!channelStates.has(channelName)) {
          return
        }

        channelStates.delete(channelName)
        unsubscribeChannel(channelName)

        if (channelStates.size === 0) {
          disconnectEchoClient()
        }
      },
    }
  }

  return gatewayClient
}

export function disconnectEchoClient() {
  if (reconnectTimer) {
    clearTimeout(reconnectTimer)
    reconnectTimer = null
  }

  if (gatewaySocket) {
    gatewaySocket.close()
    gatewaySocket = null
  }

  pendingMessages.length = 0
  channelStates.clear()
  gatewayClient = null
  currentUserId = null
  reconnectAttempts = 0
}
