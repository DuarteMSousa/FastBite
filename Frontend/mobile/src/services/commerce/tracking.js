import { graphqlRequest } from '../apiClient'
import { mapTracking, requestOptions, sessionUserId } from './core'

const ORDER_TRACKING_QUERY = `
  query OrderTracking($userId: ID!, $orderId: ID!) {
    orderTracking(user_id: $userId, order_id: $orderId) {
      eta_seconds
      last_position { latitude longitude timestamp }
      courier { user_id }
      order {
        id
        status
        total
        restaurant_name_snapshot
        user { name }
        address { latitude longitude }
        restaurant { address { latitude longitude } }
        events { event_type timestamp }
        items {
          id
          status
          quantity
          product_name_snapshot
        }
      }
      delivery {
        id
        courier_id
        status
        positionHistory { latitude longitude timestamp }
      }
    }
  }
`

export async function fetchOrderTracking({ session, orderId }) {
  const data = await graphqlRequest({
    query: ORDER_TRACKING_QUERY,
    variables: {
      userId: sessionUserId(session),
      orderId,
    },
    ...requestOptions(session),
  })

  return mapTracking(data.orderTracking)
}
