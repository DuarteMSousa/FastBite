import { buildAuthHeaders } from '../apiClient'

export const ACTIVE_ORDER_STATUSES = ['PENDING', 'CONFIRMED', 'PREPARING', 'READY', 'OUT_FOR_DELIVERY']

export function sessionUserId(session) {
  return session?.userId || session?.devUserId
}

export function requestOptions(session) {
  return {
    headers: buildAuthHeaders({
      devUserId: session?.devUserId,
      token: session?.token,
    }),
  }
}

export function mapCart(cart) {
  if (!cart) return null

  return {
    id: cart.id,
    total: cart.total,
    items: (cart.items ?? []).map((item) => ({
      id: item.id,
      restaurant_product_id: item.restaurant_product_id,
      restaurant_id: item.restaurantProduct?.restaurant_id ?? null,
      restaurant_name: item.restaurantProduct?.restaurant?.name ?? null,
      product_name: item.restaurantProduct?.product?.name ?? 'Produto',
      quantity: item.quantity,
      unit_price: item.unit_price,
      line_total: item.total_price,
    })),
  }
}

export function mapAddress(address) {
  return {
    id: address.id,
    label: address.label,
    street: address.street,
    city: address.city,
    postal_code: address.postal_code,
    country: address.country,
    latitude: Number(address.latitude),
    longitude: Number(address.longitude),
    is_default: Boolean(address.is_default),
  }
}

export function mapOrderSummary(order) {
  return {
    id: order.id,
    status: order.status,
    total: order.total,
    restaurant_name: order.restaurant_name_snapshot,
    delivery_status: order.delivery?.status ?? null,
    payment_status: order.payment?.status ?? null,
    created_at: order.created_at,
  }
}

export function mapPosition(position) {
  if (!position) return null

  return {
    lat: Number(position.latitude),
    lng: Number(position.longitude),
    recorded_at: position.timestamp,
  }
}

function nullableNumber(value) {
  if (value === null || value === undefined) return null
  const number = Number(value)
  return Number.isFinite(number) ? number : null
}

function mapRoutePoint(point) {
  const lat = nullableNumber(point?.lat)
  const lng = nullableNumber(point?.lng)

  if (lat === null || lng === null) return null

  return { lat, lng }
}

export function mapTracking(payload) {
  const order = payload?.order
  const delivery = payload?.delivery
  const positions = (delivery?.positionHistory ?? []).map(mapPosition).filter(Boolean).reverse()
  const lastPosition = mapPosition(payload?.last_position) ?? positions[0] ?? null
  const routePoints = (payload?.route_points ?? []).map(mapRoutePoint).filter(Boolean)

  return {
    order_id: order?.id ?? null,
    order_status: order?.status ?? null,
    delivery_id: delivery?.id ?? null,
    delivery_status: delivery?.status ?? null,
    courier_id: delivery?.courier_id ?? payload?.courier?.user_id ?? null,
    restaurant_name: order?.restaurant_name_snapshot ?? '',
    customer_name: order?.user?.name ?? '',
    pickup_latitude: order?.restaurant?.address?.latitude ?? null,
    pickup_longitude: order?.restaurant?.address?.longitude ?? null,
    dropoff_latitude: order?.address?.latitude ?? null,
    dropoff_longitude: order?.address?.longitude ?? null,
    route_provider: payload?.route_provider ?? 'none',
    route_distance_km: nullableNumber(payload?.route_distance_km),
    route_duration_seconds: nullableNumber(payload?.route_duration_seconds),
    route_points: routePoints,
    distance_km_remaining: nullableNumber(payload?.distance_km_remaining),
    eta_seconds: payload?.eta_seconds ?? null,
    latest_position: lastPosition,
    positions,
    events: order?.events ?? [],
    items: (order?.items ?? []).map((item) => ({
      id: item.id,
      status: item.status,
      quantity: item.quantity,
      product_name: item.product_name_snapshot,
    })),
  }
}
