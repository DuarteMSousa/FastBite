import { subscribeToOrderTrackingTopic } from './topicsRealtime'

export function subscribeToOrderTracking({
  orderId,
  authToken,
  devUserId,
  onEvent,
  onPositionUpdated,
  onError,
  onSubscribed,
}) {
  return subscribeToOrderTrackingTopic({
    orderId,
    authToken,
    devUserId,
    onEvent,
    onPositionUpdated,
    onError,
    onSubscribed,
  })
}
