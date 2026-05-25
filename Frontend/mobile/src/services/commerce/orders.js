import { graphqlRequest } from '../apiClient'
import { CLIENT_ADDRESSES_QUERY } from './addresses'
import {
  ACTIVE_ORDER_STATUSES,
  mapCart,
  mapOrderSummary,
  requestOptions,
  sessionUserId,
} from './core'

const ORDERS_QUERY = `
  query ClientOrders($userId: ID!, $statuses: [OrderStatus!], $perPage: Int) {
    getClientOrders(user_id: $userId, statuses: $statuses, per_page: $perPage) {
      id
      status
      total
      restaurant_name_snapshot
      created_at
      payment { status }
      delivery { id status }
    }
  }
`

const ORDERS_HISTORY_QUERY = `
  query ClientOrdersHistory($userId: ID!, $statuses: [OrderStatus!], $page: Int, $perPage: Int) {
    getClientOrders(user_id: $userId, statuses: $statuses, page: $page, per_page: $perPage) {
      id
      restaurant_id
      status
      total
      restaurant_name_snapshot
      created_at
      updated_at
      payment { status method }
      delivery { id status courier_id }
      items {
        id
        status
        quantity
        product_name_snapshot
        total_price
      }
      address { street city }
    }
  }
`

const CANCEL_CLIENT_ORDER_MUTATION = `
  mutation CancelClientOrder($userId: ID!, $orderId: ID!, $reason: String) {
    cancelOrderByClient(user_id: $userId, order_id: $orderId, reason: $reason) {
      id
      status
    }
  }
`

const REPEAT_CLIENT_ORDER_MUTATION = `
  mutation RepeatClientOrder($userId: ID!, $orderId: ID!) {
    repeatClientOrder(user_id: $userId, order_id: $orderId) {
      id
      total
      items {
        id
        restaurant_product_id
        quantity
        unit_price
        total_price
        restaurantProduct {
          restaurant_id
          product { name }
          restaurant { name }
        }
      }
    }
  }
`

const CLIENT_ORDER_DETAIL_QUERY = `
  query ClientOrderDetail($userId: ID!, $orderId: ID!) {
    getClientOrder(user_id: $userId, order_id: $orderId) {
      id
      restaurant_id
      status
      total
      restaurant_name_snapshot
      created_at
      updated_at
      payment { id status method paid_at amount }
      delivery { id status pickup_time delivery_time delivery_fee }
      items {
        id
        status
        quantity
        unit_price
        product_name_snapshot
        total_price
        options { id option_name_snapshot extra_price }
      }
      address { street city postal_code country }
      events { event_type timestamp }
      discounts {
        id
        name_snapshot
        discount_amount
        discount_type
      }
    }
  }
`

const PREVIEW_CHECKOUT_QUERY = `
  query PreviewCheckout($input: PreviewCheckoutInput!) {
    previewCheckout(input: $input) {
      subtotal
      delivery_fee
      discount_total
      total
      coupon_valid
      coupon_error
      discounts {
        name
        description
        amount
        type
        target
        origin_type
      }
    }
  }
`

const CHECKOUT_MUTATION = `
  mutation Checkout($input: CheckoutInput!) {
    checkoutOrder(input: $input) {
      order {
        id
        status
        total
      }
      payment {
        id
        status
        method
      }
    }
  }
`

async function fetchDefaultAddressId(session) {
  const userId = sessionUserId(session)
  const data = await graphqlRequest({
    query: CLIENT_ADDRESSES_QUERY,
    variables: { userId },
    ...requestOptions(session),
  })

  const addresses = data.getUserAddressesByUserId ?? []
  return addresses.find((address) => address.is_default)?.id ?? addresses[0]?.id ?? null
}

export async function previewCheckout({ session, addressId = null, couponCode = null } = {}) {
  const data = await graphqlRequest({
    query: PREVIEW_CHECKOUT_QUERY,
    variables: {
      input: {
        user_id: sessionUserId(session),
        address_id: addressId && String(addressId).trim() !== '' ? addressId : null,
        coupon_code: couponCode && couponCode.trim() !== '' ? couponCode.trim() : null,
      },
    },
    ...requestOptions(session),
  })

  const preview = data.previewCheckout ?? null
  if (!preview) return null

  return {
    subtotal: Number(preview.subtotal ?? 0),
    delivery_fee: Number(preview.delivery_fee ?? 0),
    discount_total: Number(preview.discount_total ?? 0),
    total: Number(preview.total ?? 0),
    coupon_valid: Boolean(preview.coupon_valid),
    coupon_error: preview.coupon_error ?? null,
    discounts: (preview.discounts ?? []).map((discount) => ({
      name: discount.name,
      description: discount.description ?? null,
      amount: Number(discount.amount ?? 0),
      type: discount.type,
      target: discount.target,
      origin_type: discount.origin_type,
    })),
  }
}

export async function checkoutCart(
  session,
  { addressId = null, paymentMethod = 'CARD', couponCode = null } = {},
) {
  const resolvedAddressId = addressId ?? session?.addressId ?? (await fetchDefaultAddressId(session))

  const data = await graphqlRequest({
    query: CHECKOUT_MUTATION,
    variables: {
      input: {
        user_id: sessionUserId(session),
        address_id: resolvedAddressId,
        payment_method: paymentMethod,
        coupon_code: couponCode && couponCode.trim() !== '' ? couponCode.trim() : null,
      },
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.checkoutOrder.order.id,
    payment_id: data.checkoutOrder.payment?.id ?? null,
    order_status: data.checkoutOrder.order.status,
    payment_status: data.checkoutOrder.payment?.status ?? null,
    payment_method: data.checkoutOrder.payment?.method ?? paymentMethod,
    total: data.checkoutOrder.order.total,
  }
}

export async function fetchMyOrders(session, { activeOnly = false, limit = 10 } = {}) {
  const data = await graphqlRequest({
    query: ORDERS_QUERY,
    variables: {
      userId: sessionUserId(session),
      statuses: activeOnly ? ACTIVE_ORDER_STATUSES : null,
      perPage: limit,
    },
    ...requestOptions(session),
  })

  const orders = (data.getClientOrders ?? []).map(mapOrderSummary)
  if (!activeOnly) return orders

  return orders.filter(
    (order) => order.status !== 'DELIVERED' && order.delivery_status !== 'DELIVERED',
  )
}

export async function fetchClientOrdersHistory({
  session,
  statuses = null,
  limit = 30,
  page = 1,
} = {}) {
  const data = await graphqlRequest({
    query: ORDERS_HISTORY_QUERY,
    variables: {
      userId: sessionUserId(session),
      statuses,
      page,
      perPage: limit,
    },
    ...requestOptions(session),
  })

  return (data.getClientOrders ?? []).map((order) => ({
    id: order.id,
    restaurant_id: order.restaurant_id,
    status: order.status,
    total: Number(order.total ?? 0),
    restaurant_name: order.restaurant_name_snapshot,
    created_at: order.created_at,
    updated_at: order.updated_at,
    payment_status: order.payment?.status ?? null,
    payment_method: order.payment?.method ?? null,
    delivery_id: order.delivery?.id ?? null,
    delivery_status: order.delivery?.status ?? null,
    courier_id: order.delivery?.courier_id ?? null,
    items_summary: (order.items ?? [])
      .map((item) => `${item.quantity}x ${item.product_name_snapshot}`)
      .join(', '),
    items_count: (order.items ?? []).length,
    address: order.address
      ? `${order.address.street}, ${order.address.city}`
      : null,
  }))
}

export async function cancelClientOrderById({ session, orderId, reason = null }) {
  const data = await graphqlRequest({
    query: CANCEL_CLIENT_ORDER_MUTATION,
    variables: {
      userId: sessionUserId(session),
      orderId,
      reason: reason && reason.trim() !== '' ? reason.trim() : null,
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.cancelOrderByClient.id,
    order_status: data.cancelOrderByClient.status,
  }
}

export async function repeatClientOrderToCart({ session, orderId }) {
  const data = await graphqlRequest({
    query: REPEAT_CLIENT_ORDER_MUTATION,
    variables: {
      userId: sessionUserId(session),
      orderId,
    },
    ...requestOptions(session),
  })

  return mapCart(data.repeatClientOrder)
}

export async function fetchClientOrderDetail({ session, orderId }) {
  const data = await graphqlRequest({
    query: CLIENT_ORDER_DETAIL_QUERY,
    variables: { userId: sessionUserId(session), orderId },
    ...requestOptions(session),
  })
  return data.getClientOrder
}
