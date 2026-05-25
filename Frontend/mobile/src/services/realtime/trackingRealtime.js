import { subscribeToOrderTrackingTopic } from './topicsRealtime'

export function subscribeToOrderTracking({
  orderId,
  authToken,
  devUserId,
  onEvent,
  onPositionUpdated,
  onError,
}) {
  return subscribeToOrderTrackingTopic({
    orderId,
    authToken,
    devUserId,
    onEvent,
    onPositionUpdated,
    onError,
  })
}
