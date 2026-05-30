import { buildAuthHeaders, graphqlRequest } from './apiClient'

const ORDER_CHATS_QUERY = `
  query GetChatsByOrderId($orderId: ID!) {
    getChatsByOrderId(order_id: $orderId) {
      id
      order_id
      type
      closed_at
      participants {
        id
        user_id
      }
      messages {
        id
        chat_id
        user_id
        content
        timestamp
      }
    }
  }
`

const CREATE_ORDER_CHAT_MUTATION = `
  mutation CreateOrderChat($input: CreateOrderChatInput!) {
    createOrderChat(input: $input) {
      id
      order_id
      type
      closed_at
      participants {
        id
        user_id
      }
      messages {
        id
        chat_id
        user_id
        content
        timestamp
      }
    }
  }
`

const SEND_MESSAGE_MUTATION = `
  mutation SendChatMessage($input: SendMessageInput!) {
    sendChatMessage(input: $input) {
      id
      chat_id
      user_id
      content
      timestamp
    }
  }
`

function requestHeaders(session) {
  return buildAuthHeaders({
    token: session?.token,
    devUserId: session?.devUserId,
  })
}

function mapChat(chat) {
  const messages = (chat.messages ?? [])
    .map((message) => {
      return {
        id: message.id,
        chat_id: message.chat_id,
        content: message.content,
        user_id: message.user_id,
        timestamp: message.timestamp,
      }
    })
    .sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime())

  return {
    id: chat.id,
    order_id: chat.order_id,
    type: chat.type,
    closed_at: chat.closed_at,
    participants: chat.participants ?? [],
    messages,
  }
}

export async function fetchOrderChats({ session, orderId }) {
  const data = await graphqlRequest({
    query: ORDER_CHATS_QUERY,
    variables: { orderId },
    headers: requestHeaders(session),
  })

  return (data.getChatsByOrderId ?? []).map(mapChat)
}

export async function createOrderChat({
  session,
  orderId,
  participantUserIds,
  type = 'CUSTOMER_RESTAURANT',
}) {
  const uniqueParticipantUserIds = Array.from(
    new Set((participantUserIds ?? []).filter(Boolean)),
  )

  if (uniqueParticipantUserIds.length < 2) {
    throw new Error('O chat precisa de pelo menos dois participantes.')
  }

  const data = await graphqlRequest({
    query: CREATE_ORDER_CHAT_MUTATION,
    variables: {
      input: {
        order_id: orderId,
        type,
        participant_user_ids: uniqueParticipantUserIds,
      },
    },
    headers: requestHeaders(session),
  })

  return mapChat(data.createOrderChat)
}

export async function sendChatMessage({ session, chatId, content }) {
  const senderUserId = session?.userId || session?.devUserId

  if (!senderUserId) {
    throw new Error('Define User ID para enviar mensagem.')
  }

  const data = await graphqlRequest({
    query: SEND_MESSAGE_MUTATION,
    variables: {
      input: {
        chat_id: chatId,
        user_id: senderUserId,
        content,
      },
    },
    headers: requestHeaders(session),
  })

  return {
    id: data.sendChatMessage.id,
    chat_id: data.sendChatMessage.chat_id,
    content: data.sendChatMessage.content,
    user_id: data.sendChatMessage.user_id,
    timestamp: data.sendChatMessage.timestamp,
  }
}
