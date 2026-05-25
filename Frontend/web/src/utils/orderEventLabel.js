const ORDER_EVENT_LABELS_PT = {
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

export function formatEventType(eventType) {
  const key = String(eventType ?? '').toUpperCase()
  if (ORDER_EVENT_LABELS_PT[key]) {
    return ORDER_EVENT_LABELS_PT[key]
  }
  return String(eventType ?? '')
    .replaceAll('_', ' ')
    .toLowerCase()
    .replace(/(^\w|\s\w)/g, (match) => match.toUpperCase())
}
