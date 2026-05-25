import { graphqlRequest } from '../apiClient'
import { mapTracking, requestOptions, sessionUserId } from './core'

const ORDER_TRACKING_QUERY = `
  query OrderTracking($userId: ID!, $orderId: ID!) {
    orderTracking(user_id: $userId, order_id: $orderId) {
      route_points { lat lng }
      route_distance_km
      route_duration_seconds
      distance_km_remaining
      eta_seconds
      route_provider
      last_position { latitude longitude timestamp }
      courier { user_id }
      order {
        id
        status
        total
        restaurant_name_snapshot
        user { id name }
        address { street city postal_code country latitude longitude }
        restaurant { address { street city postal_code country latitude longitude } }
        events { event_type timestamp }
        items {
          id
          status
          quantity
          product_name_snapshot
          options { id option_name_snapshot extra_price }
        }
      }
      delivery {
        id
        courier_id
        status
        events { event_type created_at payload }
        positionHistory { latitude longitude timestamp }
      }
    }
  }
`

const DELIVERY_TRACKING_QUERY = `
  query DeliveryTracking($deliveryId: ID!) {
    deliveryTracking(delivery_id: $deliveryId) {
      route_points { lat lng }
      route_distance_km
      route_duration_seconds
      distance_km_remaining
      eta_seconds
      route_provider
      last_position { latitude longitude timestamp }
      delivery {
        id
        courier_id
        status
        events { event_type created_at payload }
        positionHistory { latitude longitude timestamp }
        order {
          id
          status
          total
          restaurant_name_snapshot
          user { id name }
          address { street city postal_code country latitude longitude }
          restaurant { address { street city postal_code country latitude longitude } }
          events { event_type timestamp }
          items {
            id
            status
            quantity
            product_name_snapshot
            options { id option_name_snapshot extra_price }
          }
        }
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

export async function fetchDeliveryTracking({ session, deliveryId }) {
  const data = await graphqlRequest({
    query: DELIVERY_TRACKING_QUERY,
    variables: {
      deliveryId,
    },
    ...requestOptions(session),
  })

  return mapTracking(data.deliveryTracking)
}
