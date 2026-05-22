import { graphqlRequest } from '../apiClient'
import { requestOptions, sessionUserId } from './core'

const UPDATE_USER_MUTATION = `
  mutation UpdateUser($id: ID!, $input: UpdateUserInput!) {
    updateUser(id: $id, input: $input) {
      id
      name
      email
    }
  }
`

export async function updateClientUser({ session, name = null, email = null }) {
  const input = {}
  if (name && name.trim() !== '') input.name = name.trim()
  if (email && email.trim() !== '') input.email = email.trim()

  if (Object.keys(input).length === 0) {
    throw new Error('Sem alteracoes para guardar.')
  }

  const data = await graphqlRequest({
    query: UPDATE_USER_MUTATION,
    variables: {
      id: sessionUserId(session),
      input,
    },
    ...requestOptions(session),
  })

  return data.updateUser
}
