const API_BASE_URL = process.env.EXPO_PUBLIC_API_BASE_URL ?? 'http://127.0.0.1'
const AUTH_TOKEN = process.env.EXPO_PUBLIC_AUTH_BEARER_TOKEN ?? ''

function buildBaseHeaders(headers = {}) {
  const merged = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...headers,
  }

  if (!merged.Authorization && AUTH_TOKEN) {
    merged.Authorization = `Bearer ${AUTH_TOKEN}`
  }

  return merged
}

export function buildAuthHeaders({ devUserId, token } = {}) {
  const headers = {}

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  if (devUserId) {
    headers['X-Dev-User-Id'] = devUserId
  }

  return headers
}

function firstValidationMessage(validation) {
  if (!validation || typeof validation !== 'object') return ''
  const firstKey = Object.keys(validation)[0]
  const firstValue = firstKey ? validation[firstKey] : null
  if (Array.isArray(firstValue)) return firstValue[0] ?? ''
  return firstValue ? String(firstValue) : ''
}

function graphqlErrorMessage(error) {
  return (
    firstValidationMessage(error?.extensions?.validation) ||
    error?.message ||
    'GraphQL request failed.'
  )
}

export async function apiFetch(endpoint, options = {}) {
  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    headers: buildBaseHeaders(options.headers ?? {}),
    ...options,
  })

  if (!response.ok) {
    throw new Error(`Request failed with status ${response.status}`)
  }

  return response.json()
}

export async function graphqlRequest({ query, variables = {}, headers = {} }) {
  const response = await fetch(`${API_BASE_URL}/graphql`, {
    method: 'POST',
    headers: buildBaseHeaders(headers),
    body: JSON.stringify({ query, variables }),
  })

  if (!response.ok) {
    throw new Error(`GraphQL HTTP ${response.status}`)
  }

  const payload = await response.json()

  if (payload.errors?.length) {
    throw new Error(graphqlErrorMessage(payload.errors[0]))
  }

  return payload.data
}
