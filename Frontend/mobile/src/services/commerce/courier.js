import { graphqlRequest } from '../apiClient'
import { requestOptions, sessionUserId } from './core'

const COURIER_DELIVERIES_QUERY = `
  query CourierDeliveries($courierId: ID!, $statuses: [DeliveryStatus!]) {
    getDeliveriesByCourierId(courier_id: $courierId, statuses: $statuses) {
      id
      order_id
      status
      pickup_time
      delivery_time
      delivery_fee
      order {
        id
        total
        restaurant_name_snapshot
        created_at
        address { street city }
      }
    }
  }
`

const COURIER_OFFERS_QUERY = `
  query CourierOffers($courierId: ID!) {
    getDeliveryOffersByCourierId(courier_id: $courierId) {
      id
      expires_at
      delivery {
        id
        order_id
        status
        order {
          id
          status
          total
          restaurant_name_snapshot
          address { street city }
          restaurant { address { street city } }
        }
      }
    }
  }
`

const SET_COURIER_STATUS_MUTATION = `
  mutation UpdateCourierStatus($userId: ID!, $status: CourierStatus!) {
    updateCourierStatus(user_id: $userId, status: $status) {
      user_id
      status
    }
  }
`

const ACCEPT_DELIVERY_OFFER_MUTATION = `
  mutation AcceptDeliveryOffer($offerId: ID!) {
    acceptDeliveryOffer(offer_id: $offerId) {
      id
      order_id
      courier_id
      status
    }
  }
`

const REJECT_DELIVERY_OFFER_MUTATION = `
  mutation RejectDeliveryOffer($offerId: ID!, $reason: String) {
    rejectDeliveryOffer(offer_id: $offerId, reason: $reason)
  }
`

const DELIVERY_STATUS_MUTATIONS = {
  PICKED_UP: `
    mutation MarkPickedUp($deliveryId: ID!, $courierId: ID!) {
      markDeliveryPickedUp(delivery_id: $deliveryId, courier_id: $courierId) {
        id
        order_id
        status
        order { status }
      }
    }
  `,
  IN_TRANSIT: `
    mutation MarkInTransit($deliveryId: ID!, $courierId: ID!) {
      markDeliveryInTransit(delivery_id: $deliveryId, courier_id: $courierId) {
        id
        order_id
        status
        order { status }
      }
    }
  `,
  DELIVERED: `
    mutation MarkDelivered($deliveryId: ID!, $courierId: ID!) {
      markDeliveryDelivered(delivery_id: $deliveryId, courier_id: $courierId) {
        id
        order_id
        status
        order { status }
      }
    }
  `,
}

const UPDATE_COURIER_LOCATION_MUTATION = `
  mutation UpdateCourierLocation($input: UpdateCourierLocationInput!) {
    updateCourierLocation(input: $input) {
      ok
      delivery_id
      recorded_at
    }
  }
`

const MARK_DELIVERY_FAILED_MUTATION = `
  mutation MarkDeliveryFailed($deliveryId: ID!, $courierId: ID!, $reason: String!) {
    markDeliveryFailed(delivery_id: $deliveryId, courier_id: $courierId, reason: $reason) {
      id
      order_id
      status
      order { status }
    }
  }
`

export async function toggleCourierAvailability({ session, status }) {
  const data = await graphqlRequest({
    query: SET_COURIER_STATUS_MUTATION,
    variables: {
      userId: sessionUserId(session),
      status,
    },
    ...requestOptions(session),
  })

  return data.updateCourierStatus
}

export async function fetchCourierDeliveriesHistory({
  session,
  statuses = ['DELIVERED', 'FAILED'],
} = {}) {
  const courierId = sessionUserId(session)
  const data = await graphqlRequest({
    query: COURIER_DELIVERIES_QUERY,
    variables: { courierId, statuses },
    ...requestOptions(session),
  })

  return (data.getDeliveriesByCourierId ?? []).map((delivery) => ({
    delivery_id: delivery.id,
    order_id: delivery.order_id,
    delivery_status: delivery.status,
    pickup_time: delivery.pickup_time,
    delivery_time: delivery.delivery_time,
    delivery_fee: Number(delivery.delivery_fee ?? 0),
    order_total: Number(delivery.order?.total ?? 0),
    restaurant_name: delivery.order?.restaurant_name_snapshot ?? '-',
    dropoff_address: delivery.order?.address
      ? `${delivery.order.address.street}, ${delivery.order.address.city}`
      : '-',
    order_created_at: delivery.order?.created_at,
  }))
}

export async function fetchCourierAvailableDeliveries(session) {
  const courierId = sessionUserId(session)
  const data = await graphqlRequest({
    query: COURIER_OFFERS_QUERY,
    variables: { courierId },
    ...requestOptions(session),
  })

  return (data.getDeliveryOffersByCourierId ?? []).map((offer) => ({
    delivery_id: offer.delivery?.id,
    offer_token: offer.id,
    order_id: offer.delivery?.order_id,
    order_status: offer.delivery?.order?.status,
    restaurant_name: offer.delivery?.order?.restaurant_name_snapshot,
    order_total: offer.delivery?.order?.total,
    estimated_pickup_distance_km: null,
    estimated_pickup_time_min: null,
    pickup_address: offer.delivery?.order?.restaurant?.address?.street ?? '-',
    dropoff_address: offer.delivery?.order?.address?.street ?? '-',
    offer_expires_at: offer.expires_at,
  }))
}

export async function acceptDeliveryJob({ session, offerToken }) {
  const data = await graphqlRequest({
    query: ACCEPT_DELIVERY_OFFER_MUTATION,
    variables: { offerId: offerToken },
    ...requestOptions(session),
  })

  return {
    ok: true,
    delivery_id: data.acceptDeliveryOffer.id,
    order_id: data.acceptDeliveryOffer.order_id,
    courier_id: data.acceptDeliveryOffer.courier_id,
    delivery_status: data.acceptDeliveryOffer.status,
  }
}

export async function rejectDeliveryJob({ session, offerToken, reason = null }) {
  const data = await graphqlRequest({
    query: REJECT_DELIVERY_OFFER_MUTATION,
    variables: { offerId: offerToken, reason },
    ...requestOptions(session),
  })

  return { ok: Boolean(data.rejectDeliveryOffer) }
}

export async function updateDeliveryStatus({ session, deliveryId, status }) {
  const query = DELIVERY_STATUS_MUTATIONS[status]
  if (!query) {
    throw new Error(`Estado de entrega nao suportado: ${status}`)
  }

  const operationName = status === 'PICKED_UP'
    ? 'markDeliveryPickedUp'
    : status === 'IN_TRANSIT'
      ? 'markDeliveryInTransit'
      : 'markDeliveryDelivered'

  const data = await graphqlRequest({
    query,
    variables: {
      deliveryId,
      courierId: sessionUserId(session),
    },
    ...requestOptions(session),
  })

  const delivery = data[operationName]
  return {
    ok: true,
    delivery_id: delivery.id,
    order_id: delivery.order_id,
    delivery_status: delivery.status,
    order_status: delivery.order?.status ?? null,
    recorded_at: new Date().toISOString(),
  }
}

export async function updateCourierLocation({
  session,
  deliveryId,
  lat,
  lng,
  heading = null,
  speed = null,
  accuracy = null,
  recordedAt = null,
}) {
  const data = await graphqlRequest({
    query: UPDATE_COURIER_LOCATION_MUTATION,
    variables: {
      input: {
        courier_id: sessionUserId(session),
        delivery_id: deliveryId,
        latitude: lat,
        longitude: lng,
        heading,
        speed,
        accuracy,
        recorded_at: recordedAt,
      },
    },
    ...requestOptions(session),
  })

  return data.updateCourierLocation
}

export async function markDeliveryFailed({ session, deliveryId, reason }) {
  const trimmedReason = String(reason ?? '').trim()
  if (!trimmedReason) {
    throw new Error('Motivo da falha e obrigatorio.')
  }

  const data = await graphqlRequest({
    query: MARK_DELIVERY_FAILED_MUTATION,
    variables: {
      deliveryId,
      courierId: sessionUserId(session),
      reason: trimmedReason,
    },
    ...requestOptions(session),
  })

  const delivery = data.markDeliveryFailed
  return {
    ok: true,
    delivery_id: delivery.id,
    order_id: delivery.order_id,
    delivery_status: delivery.status,
    order_status: delivery.order?.status ?? null,
  }
}
