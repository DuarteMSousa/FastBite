const ORDER_LABELS = {
  PENDING: 'Pendente',
  CONFIRMED: 'Confirmado',
  PREPARING: 'A preparar',
  READY: 'Pronto',
  OUT_FOR_DELIVERY: 'Em entrega',
  DELIVERED: 'Entregue',
  CANCELLED: 'Cancelado',
}

const DELIVERY_LABELS = {
  PENDING: 'Pendente',
  PICKED_UP: 'Recolhida',
  IN_TRANSIT: 'Em trânsito',
  DELIVERED: 'Entregue',
  FAILED: 'Falhada',
}

const PAYMENT_STATUS_LABELS = {
  PENDING: 'Pendente',
  COMPLETED: 'Pago',
  FAILED: 'Falhado',
  CANCELLED: 'Cancelado',
  REFUNDED: 'Reembolsado',
}

const PAYMENT_METHOD_LABELS = {
  CASH: 'Dinheiro',
  CARD: 'Cartão',
  MBWAY: 'MB WAY',
  PAYPAL: 'PayPal',
}

const ITEM_LABELS = {
  PENDING: 'Pendente',
  PREPARING: 'A preparar',
  READY: 'Pronto',
  CANCELLED: 'Cancelado',
}

function fallbackEnumLabel(value) {
  if (!value) return '-'

  return String(value)
    .toLowerCase()
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export function orderStatusLabel(status) {
  return ORDER_LABELS[status] ?? fallbackEnumLabel(status)
}

export function deliveryStatusLabel(status) {
  return DELIVERY_LABELS[status] ?? fallbackEnumLabel(status)
}

export function paymentStatusLabel(status) {
  return PAYMENT_STATUS_LABELS[status] ?? fallbackEnumLabel(status)
}

export function paymentMethodLabel(method) {
  return PAYMENT_METHOD_LABELS[method] ?? fallbackEnumLabel(method)
}

export function itemStatusLabel(status) {
  return ITEM_LABELS[status] ?? fallbackEnumLabel(status)
}

export function statusLabelForKind(kind, status) {
  if (kind === 'delivery') return deliveryStatusLabel(status)
  if (kind === 'payment') return paymentStatusLabel(status)
  if (kind === 'item') return itemStatusLabel(status)
  return orderStatusLabel(status)
}

export function statusTone(status) {
  if (
    status === 'DELIVERED' ||
    status === 'COMPLETED' ||
    status === 'READY' ||
    status === 'PICKED_UP' ||
    status === 'OUT_FOR_DELIVERY'
  ) {
    return 'done'
  }
  if (status === 'CANCELLED' || status === 'FAILED' || status === 'REFUNDED') return 'off'
  if (status === 'PENDING') return 'pending'
  return 'prep'
}
