import { graphqlRequest } from '../apiClient'
import { requestOptions, sessionUserId } from './core'

const ORDER_CHATS_QUERY = `
  query OrderChats($orderId: ID!) {
    getChatsByOrderId(order_id: $orderId) {
      id
      order_id
      type
      closed_at
      messages {
        id
        sender_participant_id
        content
        timestamp
        read_at
      }
      participants {
        id
        user_id
      }
    }
  }
`

const CHAT_MESSAGES_QUERY = `
  query ChatMessages($chatId: ID!, $perPage: Int) {
    getMessagesByChatId(chat_id: $chatId, per_page: $perPage) {
      id
      chat_id
      sender_participant_id
      content
      timestamp
      read_at
    }
    getParticipantsByChatId(chat_id: $chatId) {
      id
      user_id
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
      messages {
        id
        sender_participant_id
        content
        timestamp
      }
      participants {
        id
        user_id
      }
    }
  }
`

const SEND_MESSAGE_MUTATION = `
  mutation SendMessage($input: SendMessageInput!) {
    sendChatMessage(input: $input) {
      id
      chat_id
      sender_participant_id
      content
      timestamp
    }
  }
`

function mapChat(chat) {
  const participantsById = new Map(
    (chat.participants ?? []).map((participant) => [participant.id, participant]),
  )

  return {
    id: chat.id,
    order_id: chat.order_id,
    type: chat.type,
    closed_at: chat.closed_at,
    messages: (chat.messages ?? []).map((message) => {
      const senderUserId = participantsById.get(message.sender_participant_id)?.user_id

      return {
        ...message,
        sender_participant_record_id: message.sender_participant_id,
        sender_participant_id: senderUserId ?? message.sender_participant_id,
        sender_user_id: senderUserId ?? null,
      }
    }),
    participants: chat.participants ?? [],
  }
}

export async function fetchOrderChats({ session, orderId }) {
  const data = await graphqlRequest({
    query: ORDER_CHATS_QUERY,
    variables: { orderId },
    ...requestOptions(session),
  })

  return (data.getChatsByOrderId ?? []).map(mapChat)
}

export async function fetchChatMessages({ session, chatId, limit = 50 }) {
  const data = await graphqlRequest({
    query: CHAT_MESSAGES_QUERY,
    variables: { chatId, perPage: limit },
    ...requestOptions(session),
  })

  const participantsById = new Map(
    (data.getParticipantsByChatId ?? []).map((participant) => [participant.id, participant]),
  )

  return (data.getMessagesByChatId ?? []).map((message) => {
    const senderUserId = participantsById.get(message.sender_participant_id)?.user_id

    return {
      ...message,
      sender_participant_record_id: message.sender_participant_id,
      sender_participant_id: senderUserId ?? message.sender_participant_id,
      sender_user_id: senderUserId ?? null,
    }
  })
}

export async function createOrderChat({ session, orderId, type, participantUserIds }) {
  const data = await graphqlRequest({
    query: CREATE_ORDER_CHAT_MUTATION,
    variables: {
      input: {
        order_id: orderId,
        type,
        participant_user_ids: participantUserIds,
      },
    },
    ...requestOptions(session),
  })

  return mapChat(data.createOrderChat)
}

export async function sendChatMessage({ session, chatId, content }) {
  const trimmed = String(content ?? '').trim()
  if (!trimmed) {
    throw new Error('A mensagem não pode estar vazia.')
  }

  const data = await graphqlRequest({
    query: SEND_MESSAGE_MUTATION,
    variables: {
      input: {
        chat_id: chatId,
        sender_user_id: sessionUserId(session),
        content: trimmed,
      },
    },
    ...requestOptions(session),
  })

  return data.sendChatMessage
}
