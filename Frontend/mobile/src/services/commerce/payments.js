import { graphqlRequest } from '../apiClient'
import { requestOptions } from './core'

const PAY_PAYMENT_MUTATION = `
  mutation PayPayment($paymentId: ID!, $transactionId: String) {
    confirmPayment(payment_id: $paymentId, transaction_id: $transactionId) {
      id
      status
      method
      transaction_id
      paid_at
    }
  }
`

const ORDER_PAYMENT_QUERY = `
  query OrderPayment($orderId: ID!) {
    getPaymentByOrderId(order_id: $orderId) {
      id
      status
      method
      transaction_id
      paid_at
      expired_at
      amount
    }
  }
`

const COUPON_BY_CODE_QUERY = `
  query CouponByCode($code: String!) {
    getCouponByCode(code: $code) {
      id
      code
      description
      type
      target
      expiry_date
    }
  }
`

export async function fetchOrderPayment({ session, orderId }) {
  const data = await graphqlRequest({
    query: ORDER_PAYMENT_QUERY,
    variables: { orderId },
    ...requestOptions(session),
  })

  return data.getPaymentByOrderId
}

export async function payPaymentNow({ session, paymentId, transactionId = null }) {
  const data = await graphqlRequest({
    query: PAY_PAYMENT_MUTATION,
    variables: {
      paymentId,
      transactionId: transactionId ?? `sim-${Date.now()}`,
    },
    ...requestOptions(session),
  })

  return data.confirmPayment
}

export async function fetchCouponByCode({ session, code }) {
  const data = await graphqlRequest({
    query: COUPON_BY_CODE_QUERY,
    variables: { code },
    ...requestOptions(session),
  })

  return data.getCouponByCode
}
