export const OFFER_EXPIRY_FALLBACK_SECONDS = 30

export function parseServerDateMs(value) {
  if (!value) return null

  const normalized = String(value).trim().replace(' ', 'T')
  if (!normalized) return null

  const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized)
  const timestamp = Date.parse(hasTimezone ? normalized : `${normalized}Z`)

  return Number.isNaN(timestamp) ? null : timestamp
}

export function statusText(status) {
  if (status === 'AVAILABLE') return 'Online'
  if (status === 'BUSY') return 'Ocupado'
  return 'Offline'
}

export function distanceMeters(a, b) {
  if (!a || !b) return null

  const earthRadius = 6371000
  const dLat = ((Number(b.lat) - Number(a.lat)) * Math.PI) / 180
  const dLng = ((Number(b.lng) - Number(a.lng)) * Math.PI) / 180
  const lat1 = (Number(a.lat) * Math.PI) / 180
  const lat2 = (Number(b.lat) * Math.PI) / 180

  const value =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2)

  return earthRadius * (2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value)))
}
