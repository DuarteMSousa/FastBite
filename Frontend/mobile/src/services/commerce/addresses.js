import { graphqlRequest } from '../apiClient'
import { mapAddress, requestOptions, sessionUserId } from './core'

export const CLIENT_ADDRESSES_QUERY = `
  query ClientAddresses($userId: ID!) {
    getUserAddressesByUserId(user_id: $userId) {
      id
      label
      street
      city
      postal_code
      country
      latitude
      longitude
      is_default
    }
  }
`

const CREATE_CLIENT_ADDRESS_MUTATION = `
  mutation CreateClientAddress($userId: ID!, $input: CreateUserAddressInput!) {
    createUserAddress(user_id: $userId, input: $input) {
      id
      label
      street
      city
      postal_code
      country
      latitude
      longitude
      is_default
    }
  }
`

const UPDATE_CLIENT_ADDRESS_MUTATION = `
  mutation UpdateClientAddress($userId: ID!, $addressId: ID!, $input: UpdateUserAddressInput!) {
    updateUserAddress(user_id: $userId, address_id: $addressId, input: $input) {
      id
      label
      street
      city
      postal_code
      country
      latitude
      longitude
      is_default
    }
  }
`

const DELETE_CLIENT_ADDRESS_MUTATION = `
  mutation DeleteClientAddress($userId: ID!, $addressId: ID!) {
    deleteUserAddress(user_id: $userId, address_id: $addressId)
  }
`

const SET_DEFAULT_CLIENT_ADDRESS_MUTATION = `
  mutation SetDefaultClientAddress($userId: ID!, $addressId: ID!) {
    setDefaultUserAddress(user_id: $userId, address_id: $addressId) {
      id
      is_default
    }
  }
`

export async function fetchClientAddresses(session) {
  const data = await graphqlRequest({
    query: CLIENT_ADDRESSES_QUERY,
    variables: { userId: sessionUserId(session) },
    ...requestOptions(session),
  })

  return (data.getUserAddressesByUserId ?? []).map(mapAddress)
}

export async function createUserAddress({ session, input }) {
  const data = await graphqlRequest({
    query: CREATE_CLIENT_ADDRESS_MUTATION,
    variables: {
      userId: sessionUserId(session),
      input: {
        street: input.street,
        city: input.city,
        postal_code: input.postal_code,
        country: input.country,
        latitude: Number(input.latitude),
        longitude: Number(input.longitude),
        label: input.label ?? null,
        is_default: Boolean(input.is_default),
      },
    },
    ...requestOptions(session),
  })

  return mapAddress(data.createUserAddress)
}

export async function updateUserAddress({ session, addressId, input }) {
  const data = await graphqlRequest({
    query: UPDATE_CLIENT_ADDRESS_MUTATION,
    variables: {
      userId: sessionUserId(session),
      addressId,
      input,
    },
    ...requestOptions(session),
  })

  return mapAddress(data.updateUserAddress)
}

export async function deleteUserAddress({ session, addressId }) {
  const data = await graphqlRequest({
    query: DELETE_CLIENT_ADDRESS_MUTATION,
    variables: {
      userId: sessionUserId(session),
      addressId,
    },
    ...requestOptions(session),
  })

  return { ok: Boolean(data.deleteUserAddress) }
}

export async function setDefaultUserAddress({ session, addressId }) {
  const data = await graphqlRequest({
    query: SET_DEFAULT_CLIENT_ADDRESS_MUTATION,
    variables: {
      userId: sessionUserId(session),
      addressId,
    },
    ...requestOptions(session),
  })

  return { ok: true, id: data.setDefaultUserAddress.id }
}
