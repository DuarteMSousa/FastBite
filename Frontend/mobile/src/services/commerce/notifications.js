import { graphqlRequest } from '../apiClient'
import { requestOptions, sessionUserId } from './core'

const CLIENT_NOTIFICATIONS_QUERY = `
  query ClientNotifications($userId: ID!, $unreadOnly: Boolean!, $page: Int!, $perPage: Int!) {
    getNotificationsByUserId(user_id: $userId, unread_only: $unreadOnly, page: $page, per_page: $perPage) {
      items {
        id
        type
        title
        message
        sent_at
        read_at
      }
    }
  }
`

const MARK_NOTIFICATION_READ_MUTATION = `
  mutation MarkNotificationRead($userId: ID!, $notificationId: ID!) {
    markNotificationAsRead(user_id: $userId, notification_id: $notificationId) {
      ok
      notification_id
      read_at
    }
  }
`

const MARK_ALL_NOTIFICATIONS_READ_MUTATION = `
  mutation MarkAllClientNotificationsRead($userId: ID!) {
    markAllNotificationsAsRead(user_id: $userId) {
      ok
      affected_count
    }
  }
`

export async function fetchClientNotifications({
  session,
  unreadOnly = false,
  limit = 50,
} = {}) {
  const data = await graphqlRequest({
    query: CLIENT_NOTIFICATIONS_QUERY,
    variables: {
      userId: sessionUserId(session),
      unreadOnly,
      page: 1,
      perPage: limit,
    },
    ...requestOptions(session),
  })

  return (data.getNotificationsByUserId?.items ?? []).map((notification) => ({
    id: notification.id,
    type: notification.type,
    title: notification.title,
    message: notification.message,
    sent_at: notification.sent_at,
    read_at: notification.read_at,
    read: Boolean(notification.read_at),
  }))
}

export async function markClientNotificationRead({ session, notificationId }) {
  const data = await graphqlRequest({
    query: MARK_NOTIFICATION_READ_MUTATION,
    variables: {
      userId: sessionUserId(session),
      notificationId,
    },
    ...requestOptions(session),
  })

  return data.markNotificationAsRead
}

export async function markAllNotificationsAsRead({ session }) {
  const data = await graphqlRequest({
    query: MARK_ALL_NOTIFICATIONS_READ_MUTATION,
    variables: {
      userId: sessionUserId(session),
    },
    ...requestOptions(session),
  })

  return data.markAllNotificationsAsRead
}
