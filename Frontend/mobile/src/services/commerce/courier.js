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
        user { id name }
        address { street city postal_code country }
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
          user { id name }
          address { street city postal_code country }
          restaurant { address { street city postal_code country } }
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
      delivery_fee
      order {
        id
        status
        total
        restaurant_name_snapshot
        user { id name }
        address { street city postal_code country }
        restaurant { address { street city postal_code country } }
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

function addressLine(address) {
  if (!address) return null
  if (typeof address === 'string') return address
  return [address.street, address.city, address.postal_code].filter(Boolean).join(', ') || null
}

function mapCourierOrderItem(item) {
  return {
    id: item?.id,
    status: item?.status ?? null,
    quantity: Number(item?.quantity ?? 0),
    product_name: item?.product_name ?? item?.product_name_snapshot ?? 'Produto',
    options: (item?.options ?? []).map((option) => ({
      id: option?.id,
      name: option?.name ?? option?.option_name ?? option?.option_name_snapshot,
      extra_price: Number(option?.extra_price ?? 0),
    })),
  }
}

function resolveOfferSource(payload) {
  return payload?.offer ?? payload?.data?.offer ?? payload
}

export function mapCourierOfferPayload(payload) {
  const offer = resolveOfferSource(payload) ?? {}
  const delivery = offer.delivery ?? payload?.delivery ?? {}
  const order = offer.order ?? payload?.order ?? delivery?.order ?? {}
  const pickupAddress =
    offer.pickup_address ??
    payload?.pickupAddress ??
    payload?.pickup_address ??
    addressLine(order?.restaurant?.address)
  const dropoffAddress =
    offer.dropoff_address ??
    payload?.dropoffAddress ??
    payload?.dropoff_address ??
    addressLine(order?.address)

  return {
    delivery_id: offer.delivery_id ?? payload?.delivery_id ?? payload?.deliveryId ?? delivery?.id,
    offer_token: offer.offer_token ?? offer.id ?? payload?.offer_id ?? payload?.offerId,
    order_id:
      offer.order_id ??
      payload?.order_id ??
      payload?.orderId ??
      delivery?.order_id ??
      order?.id,
    order_status: offer.order_status ?? order?.status ?? null,
    restaurant_name:
      offer.restaurant_name ??
      payload?.restaurantName ??
      payload?.restaurant_name ??
      order?.restaurant_name_snapshot,
    customer_id: offer.customer_id ?? order?.user_id ?? order?.user?.id ?? null,
    customer_name:
      offer.customer_name ??
      payload?.customerName ??
      payload?.customer_name ??
      order?.user?.name ??
      null,
    order_total: Number(offer.order_total ?? payload?.orderTotal ?? order?.total ?? 0),
    estimated_pickup_distance_km: offer.estimated_pickup_distance_km ?? null,
    estimated_pickup_time_min: offer.estimated_pickup_time_min ?? null,
    pickup_address: pickupAddress ?? '-',
    dropoff_address: dropoffAddress ?? '-',
    items: (offer.items ?? payload?.items ?? order?.items ?? []).map(mapCourierOrderItem),
    offer_expires_at: offer.expires_at ?? payload?.expires_at ?? payload?.expiresAt,
  }
}

function mapDeliveryToActive(delivery) {
  const order = delivery?.order ?? {}

  return {
    delivery_id: delivery?.id,
    order_id: delivery?.order_id,
    courier_id: delivery?.courier_id,
    delivery_status: delivery?.status,
    delivery_fee: Number(delivery?.delivery_fee ?? 0),
    order_status: order?.status ?? null,
    order_total: Number(order?.total ?? 0),
    restaurant_name: order?.restaurant_name_snapshot ?? '-',
    customer_id: order?.user?.id ?? order?.user_id ?? null,
    customer_name: order?.user?.name ?? null,
    pickup_address: addressLine(order?.restaurant?.address) ?? '-',
    dropoff_address: addressLine(order?.address) ?? '-',
    items: (order?.items ?? []).map(mapCourierOrderItem),
  }
}

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
    customer_name: delivery.order?.user?.name ?? null,
    items: (delivery.order?.items ?? []).map(mapCourierOrderItem),
    dropoff_address: delivery.order?.address
      ? addressLine(delivery.order.address)
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

  return (data.getDeliveryOffersByCourierId ?? []).map(mapCourierOfferPayload)
}

export async function acceptDeliveryJob({ session, offerToken }) {
  const data = await graphqlRequest({
    query: ACCEPT_DELIVERY_OFFER_MUTATION,
    variables: { offerId: offerToken },
    ...requestOptions(session),
  })

  return {
    ok: true,
    ...mapDeliveryToActive(data.acceptDeliveryOffer),
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
    throw new Error(`Estado de entrega não suportado: ${status}`)
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
