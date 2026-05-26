export function MoneyText({ value, currency = 'EUR', className = '' }) {
  const numeric = Number(value ?? 0)
  return <span className={className}>{`${numeric.toFixed(2)} ${currency}`}</span>
}
