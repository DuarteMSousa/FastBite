import { styles } from './styles'

export const INBOX_MAX_ITEMS = 60

export const ICON = {
  user: '\u{1F464}',
  search: '\u{1F50D}',
  star: '\u2605',
  time: '\u{1F551}',
  bike: '\u{1F6B4}',
  plus: '+',
  cart: '\u{1F6D2}',
  back: '\u2190',
  minus: '\u2212',
  close: '\u00D7',
  check: '\u2713',
  bell: '\u{1F514}',
  prep: '\u{1F551}',
}

export const CANCELLABLE_STATUSES = ['PENDING', 'COURIER_ASSIGNED', 'CONFIRMED']
export const TRACKABLE_STATUSES = ['COURIER_ASSIGNED', 'CONFIRMED', 'PREPARING', 'READY', 'OUT_FOR_DELIVERY']

export function formatCurrency(value) {
  return `EUR ${Number(value ?? 0).toFixed(2)}`
}

export function statusLabel(status) {
  if (status === 'PENDING') return 'Pendente'
  if (status === 'COURIER_ASSIGNED') return 'Estafeta atribuído'
  if (status === 'CONFIRMED') return 'Confirmado'
  if (status === 'PREPARING') return 'A preparar'
  if (status === 'READY') return 'Pronto'
  if (status === 'OUT_FOR_DELIVERY') return 'Em entrega'
  if (status === 'DELIVERED') return 'Entregue'
  if (status === 'CANCELLED') return 'Cancelado'
  if (status === 'PICKED_UP') return 'Recolhida'
  if (status === 'IN_TRANSIT') return 'A caminho'
  if (status === 'FAILED') return 'Falhada'
  if (status === 'COMPLETED') return 'Concluído'
  return status ?? '-'
}

export function orderItemStatusLabel(status) {
  if (status === 'PENDING') return 'Pendente'
  if (status === 'PREPARING') return 'Em preparação'
  if (status === 'READY') return 'Pronto'
  if (status === 'CANCELLED') return 'Cancelado'
  return status ?? '-'
}

export function orderItemStatusChipStyle(status) {
  if (status === 'READY') return styles.orderStatusOk
  if (status === 'CANCELLED') return styles.orderStatusOff
  if (status === 'PREPARING') return styles.orderStatusGo
  return styles.orderStatusPending
}

export function paymentMethodLabel(method) {
  if (method === 'CASH') return 'Dinheiro à entrega'
  if (method === 'CARD') return 'Cartão'
  if (method === 'MBWAY') return 'MB Way'
  if (method === 'PAYPAL') return 'PayPal'
  return method
}

export function orderStatusChipStyle(status) {
  if (status === 'DELIVERED') return styles.orderStatusOk
  if (status === 'CANCELLED') return styles.orderStatusOff
  if (status === 'OUT_FOR_DELIVERY' || status === 'READY') return styles.orderStatusGo
  if (status === 'COURIER_ASSIGNED') return styles.orderStatusPending
  return styles.orderStatusPending
}

const EVENT_LABELS_PT = {
  ORDER_CREATED: 'Pedido criado',
  ORDER_PAYMENT_COMPLETED: 'Pagamento confirmado',
  ORDER_CONFIRMED: 'Pedido confirmado',
  ORDER_REJECTED: 'Pedido rejeitado',
  ORDER_PREPARING: 'Em preparação',
  ORDER_READY: 'Pedido pronto',
  ORDER_COURIER_ASSIGNED: 'Estafeta atribuído',
  ORDER_PICKED_UP: 'Recolhido pelo estafeta',
  ORDER_OUT_FOR_DELIVERY: 'Em entrega',
  ORDER_DELIVERED: 'Entregue ao cliente',
  ORDER_CANCELLED: 'Pedido cancelado',
  ORDER_STATUS_UPDATED: 'Estado do pedido atualizado',
  DELIVERY_ACCEPTED: 'Entrega aceite',
  DELIVERY_PICKED_UP: 'Encomenda recolhida',
  DELIVERY_IN_TRANSIT: 'A caminho',
  DELIVERY_DELIVERED: 'Entregue',
  DELIVERY_FAILED: 'Entrega falhada',
  PAYMENT_CREATED: 'Pagamento criado',
  PAYMENT_COMPLETED: 'Pagamento confirmado',
  PAYMENT_FAILED: 'Pagamento falhado',
  PAYMENT_EXPIRED: 'Pagamento expirado',
  PAYMENT_CANCELLED: 'Pagamento cancelado',
  PAYMENT_REFUNDED: 'Pagamento reembolsado',
  JOB_OFFERED: 'Oferta de entrega',
  JOB_ACCEPTED: 'Oferta aceite',
  JOB_REJECTED: 'Oferta recusada',
  JOB_EXPIRED: 'Oferta expirada',
  COURIER_POSITION_UPDATED: 'Posição do estafeta atualizada',
  CHAT_MESSAGE_SENT: 'Nova mensagem',
  USER_NOTIFICATION_CREATED: 'Nova notificação',
}

export function eventTypeLabel(eventType) {
  const key = String(eventType ?? '').toUpperCase()
  if (EVENT_LABELS_PT[key]) return EVENT_LABELS_PT[key]
  return String(eventType ?? '')
    .replaceAll('_', ' ')
    .toLowerCase()
    .replace(/(^\w|\s\w)/g, (match) => match.toUpperCase())
}
