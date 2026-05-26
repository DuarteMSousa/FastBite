import { graphqlRequest } from './apiClient'

const CREATE_USER_MUTATION = `
  mutation CreateUser($input: CreateUserInput!) {
    createUser(input: $input) {
      id
      name
      email
    }
  }
`

const LOGIN_USER_MUTATION = `
  mutation AuthenticateByCredentials($email: String!, $password: String!) {
    authenticateByCredentials(email: $email, password: $password) {
      id
      name
      email
    }
  }
`

const USER_CAPABILITIES_QUERY = `
  query GetUserCapabilities($userId: ID!) {
    getCourierByUserId(user_id: $userId) {
      user_id
    }
    getRestaurantsByManagerUserId(user_id: $userId) {
      id
    }
  }
`

const ENSURE_COURIER_PROFILE_MUTATION = `
  mutation EnsureCourierProfile($userId: ID!) {
    ensureCourierProfile(user_id: $userId) {
      user_id
    }
  }
`

function buildSession({ user, email, role, token = '' }) {
  return {
    userId: user.id,
    devUserId: user.id,
    role,
    name: user.name || email.split('@')[0] || 'utilizador',
    email: user.email ?? email,
    token: String(token ?? '').trim(),
  }
}

async function resolveMobileRole(userId) {
  const data = await graphqlRequest({
    query: USER_CAPABILITIES_QUERY,
    variables: { userId },
  })

  if (data.getCourierByUserId?.user_id) return 'courier'
  if ((data.getRestaurantsByManagerUserId ?? []).length > 0) {
    throw new Error('Conta sem perfil mobile.')
  }

  return 'customer'
}

export async function loginMobileUser({ email, password, token = '' }) {
  const trimmedEmail = String(email ?? '').trim()
  const trimmedPassword = String(password ?? '').trim()

  if (!trimmedEmail || !trimmedPassword) {
    throw new Error('Preencha o email e a palavra-passe.')
  }

  const data = await graphqlRequest({
    query: LOGIN_USER_MUTATION,
    variables: {
      email: trimmedEmail,
      password: trimmedPassword,
    },
  })

  const user = data?.authenticateByCredentials

  if (!user?.id) {
    throw new Error('Não foi possível autenticar o utilizador.')
  }

  const role = await resolveMobileRole(user.id)

  return buildSession({ user, email: trimmedEmail, role, token })
}

export async function registerMobileUser({ email, password, name, role = 'customer', token = '' }) {
  const trimmedEmail = String(email ?? '').trim()
  const trimmedPassword = String(password ?? '').trim()
  const trimmedName = String(name ?? '').trim()

  if (!trimmedName || !trimmedEmail || !trimmedPassword) {
    throw new Error('Preencha o nome, o email e a palavra-passe.')
  }

  try {
    const data = await graphqlRequest({
      query: CREATE_USER_MUTATION,
      variables: {
        input: {
          name: trimmedName,
          email: trimmedEmail,
          password: trimmedPassword,
        },
      },
    })

    if (role === 'courier') {
      await graphqlRequest({
        query: ENSURE_COURIER_PROFILE_MUTATION,
        variables: {
          userId: data.createUser.id,
        },
      })
    }
  } catch (error) {
    const message = String(error?.message ?? '')
    if (
      message.includes('users_email_unique') ||
      message.toLowerCase().includes('unique constraint') ||
      message.toLowerCase().includes('unique') ||
      message.toLowerCase().includes('já está registado') ||
      message.toLowerCase().includes('ja esta registado') ||
      message.toLowerCase().includes('duplicate') ||
      message.toLowerCase().includes('already')
    ) {
      throw new Error('Este email já está registado.', { cause: error })
    }

    throw error
  }

  return loginMobileUser({
    email: trimmedEmail,
    password: trimmedPassword,
    token,
  })
}
