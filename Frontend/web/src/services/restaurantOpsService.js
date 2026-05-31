import { buildAuthHeaders, graphqlRequest } from './apiClient'

function requestOptions(session) {
  return {
    headers: buildAuthHeaders({
      devUserId: session?.devUserId,
      token: session?.token,
    }),
  }
}

function currentUserId(session) {
  return session?.userId || session?.devUserId || 'system'
}

function normalizeCategoryName(value) {
  return String(value ?? '').trim().toLowerCase()
}

function mapOrder(order) {
  return {
    order_id: order.id,
    restaurant_id: order.restaurant_id,
    customer_id: order.user_id,
    customer_name: order.user?.name ?? null,
    order_status: order.status,
    total: order.total,
    delivery_address: order.address
      ? `${order.address.street}, ${order.address.city}`
      : '-',
    created_at: order.created_at,
    delivery_id: order.delivery?.id ?? null,
    delivery_status: order.delivery?.status ?? null,
    courier_id: order.delivery?.courier_id ?? null,
    payment_method: order.payment?.method ?? null,
    payment_status: order.payment?.status ?? null,
    events: (order.events ?? []).map((event) => ({
      event_type: event.event_type,
      timestamp: event.timestamp,
    })),
    items: (order.items ?? []).map((item) => ({
      order_item_id: item.id,
      name: item.product_name_snapshot,
      quantity: item.quantity,
      status: item.status,
      total_price: item.total_price,
    })),
  }
}

export function mapRestaurantOrder(order) {
  return mapOrder(order)
}

function mapRestaurantProduct(item, categories = []) {
  const category = categories.find((entry) => entry.id === item.product?.category_id)
  return {
    restaurant_product_id: item.id,
    product_id: item.product_id,
    restaurant_id: item.restaurant_id,
    category: category?.name ?? '',
    name: item.product?.name ?? 'Produto',
    description: item.product?.description ?? '',
    price: item.local_price ?? item.product?.price ?? 0,
    is_available: Boolean(item.is_available),
    estimated_preparation_time_min: item.estimated_preparation_time_min,
  }
}

function mapNotification(notification) {
  return {
    id: notification.id,
    type: notification.type,
    title: notification.title,
    message: notification.message,
    timestamp: notification.sent_at,
    read: Boolean(notification.read_at),
    read_at: notification.read_at,
  }
}

const LOGIN_USER_MUTATION = `
  mutation AuthenticateByCredentials($email: String!, $password: String!) {
    authenticateByCredentials(email: $email, password: $password) {
      id
      name
      email
    }
  }
`

const CREATE_RESTAURANT_USER_MUTATION = `
  mutation CreateRestaurantUser($input: CreateUserInput!) {
    createUser(input: $input) {
      id
      name
      email
    }
  }
`

const RESTAURANTS_BY_MANAGER_USER_QUERY = `
  query GetRestaurantsByManagerUserId($userId: ID!) {
    getRestaurantsByManagerUserId(user_id: $userId) {
        id
        name
        chain_id
    }
  }
`

const CHAIN_BY_MANAGER_USER_QUERY = `
  query GetRestaurantChainByManagerUserId($userId: ID!) {
    getRestaurantChainByManagerUserId(user_id: $userId) {
      id
      name
    }
  }
`

const SEARCH_RESTAURANT_CHAINS_QUERY = `
  query SearchRestaurantChains($input: SearchRestaurantChainsInput) {
    searchRestaurantChains(input: $input) {
      id
      name
    }
  }
`

const CREATE_RESTAURANT_CHAIN_MUTATION = `
  mutation CreateRestaurantChain($input: CreateRestaurantChainInput!) {
    createRestaurantChain(input: $input) {
      id
      name
    }
  }
`

const CREATE_RESTAURANT_MUTATION = `
  mutation CreateRestaurant($input: CreateRestaurantInput!) {
    createRestaurant(input: $input) {
      id
      name
      chain_id
    }
  }
`

const ASSIGN_CHAIN_MANAGER_MUTATION = `
  mutation AssignChainManager($userId: ID!, $chainId: ID!) {
    assignChainManager(user_id: $userId, chain_id: $chainId) {
      user_id
      chain_id
    }
  }
`

const ASSIGN_LOCAL_MANAGER_MUTATION = `
  mutation AssignLocalManager($userId: ID!, $restaurantId: ID!) {
    assignLocalManager(user_id: $userId, restaurant_id: $restaurantId) {
      user_id
      restaurant_id
    }
  }
`

const RESTAURANT_ORDER_DETAIL_QUERY = `
  query GetRestaurantOrder($restaurantId: ID!, $orderId: ID!) {
    getRestaurantOrder(restaurant_id: $restaurantId, order_id: $orderId) {
      id
      user_id
      restaurant_id
      status
      total
      restaurant_name_snapshot
      created_at
      updated_at
      user { id name email }
      restaurant { id name address { latitude longitude street city } }
      address { street city postal_code country latitude longitude }
      payment { id method status amount transaction_id paid_at expired_at }
      delivery {
        id
        courier_id
        status
        pickup_time
        delivery_time
        delivery_fee
        courier {
          user_id
          status
          latitude
          longitude
          last_location_update
          user { name }
        }
      }
      events { event_type timestamp payload }
      items {
        id
        status
        quantity
        unit_price
        product_name_snapshot
        total_price
        options {
          id
          option_name_snapshot
          extra_price
        }
      }
      discounts {
        id
        name_snapshot
        discount_amount
        discount_type
        discount_target
      }
    }
  }
`

const RESTAURANT_ORDERS_HISTORY_QUERY = `
  query GetRestaurantOrders($restaurantId: ID!, $statuses: [OrderStatus!], $page: Int, $perPage: Int) {
    getRestaurantOrders(restaurant_id: $restaurantId, statuses: $statuses, page: $page, per_page: $perPage) {
      id
      user_id
      status
      total
      restaurant_name_snapshot
      created_at
      updated_at
      user { id name }
      address { street city }
      payment { method status }
      delivery { id status }
    }
  }
`

const RESTAURANT_ACTIVE_ORDERS_QUERY = `
  query GetActiveRestaurantOrders($restaurantId: ID!) {
    getActiveRestaurantOrders(restaurant_id: $restaurantId) {
      id
      user_id
      restaurant_id
      status
      total
      created_at
      user { id name }
      address { street city }
      payment { method status }
      delivery { id courier_id status }
      events { event_type timestamp }
      items {
        id
        status
        quantity
        product_name_snapshot
        total_price
      }
    }
  }
`

const ACCEPT_RESTAURANT_ORDER_MUTATION = `
  mutation AcceptOrderByRestaurant($input: RestaurantOrderDecisionInput!) {
    acceptOrderByRestaurant(input: $input) {
      id
      status
    }
  }
`

const REJECT_RESTAURANT_ORDER_MUTATION = `
  mutation RejectOrderByRestaurant($input: RestaurantOrderDecisionInput!) {
    rejectOrderByRestaurant(input: $input) {
      id
      status
    }
  }
`

const START_PREPARING_ORDER_MUTATION = `
  mutation StartPreparingOrder($input: RestaurantOrderDecisionInput!) {
    startPreparingOrder(input: $input) {
      id
      status
    }
  }
`

const MARK_ORDER_READY_MUTATION = `
  mutation MarkOrderReady($input: RestaurantOrderDecisionInput!) {
    markOrderReady(input: $input) {
      id
      status
    }
  }
`

const UPDATE_ORDER_ITEM_STATUS_MUTATION = `
  mutation UpdateOrderItemStatus($input: UpdateOrderItemStatusInput!) {
    updateOrderItemStatus(input: $input) {
      id
      status
      items {
        id
        status
      }
    }
  }
`

const RESTAURANT_MENU_QUERY = `
  query GetRestaurantMenu($restaurantId: ID!) {
    getRestaurantMenu(restaurant_id: $restaurantId) {
      categories { id name }
      products {
        id
        restaurant_id
        product_id
        local_price
        is_available
        estimated_preparation_time_min
        product {
          id
          category_id
          name
          price
          description
        }
      }
    }
  }
`

const CHAIN_PROMOTIONS_QUERY = `
  query GetPromotionsByChainId($chainId: ID!) {
    getPromotionsByChainId(chain_id: $chainId) {
      id
      name
      description
      type
      target
      discount
      start_date
      end_date
      promotionItems {
        id
        parent_type
        parent_id
        item_id
        product { id name }
        category { id name }
      }
    }
  }
`

const CHAIN_COUPONS_QUERY = `
  query GetCouponsByChainId($chainId: ID!) {
    getCouponsByChainId(chain_id: $chainId) {
      id
      code
      description
      type
      target
      discount
      expiry_date
      promotionItems {
        id
        parent_type
        parent_id
        item_id
        product { id name }
        category { id name }
      }
    }
  }
`

const CREATE_PROMOTION_MUTATION = `
  mutation CreatePromotion($input: CreatePromotionInput!) {
    createPromotion(input: $input) {
      id
      name
    }
  }
`

const UPDATE_PROMOTION_MUTATION = `
  mutation UpdatePromotion($id: ID!, $input: UpdatePromotionInput!) {
    updatePromotion(id: $id, input: $input) {
      id
      name
    }
  }
`

const UPDATE_COUPON_MUTATION = `
  mutation UpdateCoupon($id: ID!, $input: UpdateCouponInput!) {
    updateCoupon(id: $id, input: $input) {
      id
      code
    }
  }
`

const CHAIN_PRODUCTS_QUERY = `
  query GetCategoriesByChainId($chainId: ID!) {
    getCategoriesByChainId(chain_id: $chainId) {
      id
      name
      products { id name }
    }
  }
`

const CHAIN_CATALOG_QUERY = `
  query GetChainCatalog($chainId: ID!) {
    getCategoriesByChainId(chain_id: $chainId) {
      id
      name
      products {
        id
        name
        price
        description
        category_id
      }
    }
  }
`

const DELETE_PRODUCT_MUTATION = `
  mutation DeleteProduct($id: ID!) {
    deleteProduct(id: $id)
  }
`

const DELETE_PROMOTION_MUTATION = `
  mutation DeletePromotion($id: ID!) {
    deletePromotion(id: $id)
  }
`

const CREATE_COUPON_MUTATION = `
  mutation CreateCoupon($input: CreateCouponInput!) {
    createCoupon(input: $input) {
      id
      code
    }
  }
`

const DELETE_COUPON_MUTATION = `
  mutation DeleteCoupon($id: ID!) {
    deleteCoupon(id: $id)
  }
`

const TARGET_REVIEWS_QUERY = `
  query GetReviewsByTarget($targetType: ReviewTargetTypeInput!, $targetId: ID!, $perPage: Int) {
    getReviewsByTarget(target_type: $targetType, target_id: $targetId, per_page: $perPage) {
      id
      user_id
      rating
      comment
      target_type
      target_id
      created_at
    }
  }
`

const RESTAURANT_QUERY = `
  query GetRestaurantById($id: ID!) {
    getRestaurantById(id: $id) {
      id
      name
      chain_id
      opening_hours
      closing_hours
      delivery_radius
      rating_sum
      rating_count
      chain { id name }
      address {
        street
        city
        postal_code
        country
        latitude
        longitude
      }
    }
  }
`

const RESTAURANT_CHAIN_QUERY = `
  query GetRestaurantChainById($id: ID!) {
    getRestaurantChainById(id: $id) {
      id
      name
      created_at
      updated_at
    }
  }
`

const CHAIN_RESTAURANTS_QUERY = `
  query GetRestaurantsByChainId($chainId: ID!) {
    getRestaurantsByChainId(chain_id: $chainId) {
      id
      name
      chain_id
      opening_hours
      closing_hours
      delivery_radius
      rating_sum
      rating_count
      address {
        street
        city
        postal_code
        country
        latitude
        longitude
      }
    }
  }
`

const UPDATE_RESTAURANT_MUTATION = `
  mutation UpdateRestaurant($id: ID!, $input: UpdateRestaurantInput!) {
    updateRestaurant(id: $id, input: $input) {
      id
      name
      chain_id
      opening_hours
      closing_hours
      delivery_radius
      rating_sum
      rating_count
      chain { id name }
      address {
        street
        city
        postal_code
        country
        latitude
        longitude
      }
    }
  }
`

const UPDATE_RESTAURANT_CHAIN_MUTATION = `
  mutation UpdateRestaurantChain($id: ID!, $input: UpdateRestaurantChainInput!) {
    updateRestaurantChain(id: $id, input: $input) {
      id
      name
      updated_at
    }
  }
`

const CHAIN_CATEGORIES_QUERY = `
  query GetCategoriesByChainId($chainId: ID!) {
    getCategoriesByChainId(chain_id: $chainId) {
      id
      name
    }
  }
`

const UPDATE_CATEGORY_MUTATION = `
  mutation UpdateCategory($id: ID!, $input: UpdateCategoryInput!) {
    updateCategory(id: $id, input: $input) {
      id
      name
    }
  }
`

const DELETE_CATEGORY_MUTATION = `
  mutation DeleteCategory($id: ID!) {
    deleteCategory(id: $id)
  }
`

const PRODUCT_OPTION_GROUPS_QUERY_ADMIN = `
  query GetProductOptionGroups($productId: ID!) {
    getProductOptionGroups(product_id: $productId) {
      id
      name
      min_options
      max_options
      options {
        id
        name
        extra_price
        default_option
      }
    }
  }
`

const UPDATE_PRODUCT_MUTATION = `
  mutation UpdateProduct($id: ID!, $input: UpdateProductInput!) {
    updateProduct(id: $id, input: $input) {
      id
      name
      price
    }
  }
`

const CREATE_CATEGORY_MUTATION = `
  mutation CreateCategory($input: CreateCategoryInput!) {
    createCategory(input: $input) {
      id
      name
    }
  }
`

const CREATE_PRODUCT_MUTATION = `
  mutation CreateProduct($input: CreateProductInput!) {
    createProduct(input: $input) {
      id
      name
      price
    }
  }
`

const CREATE_RESTAURANT_PRODUCT_MUTATION = `
  mutation CreateRestaurantProduct($input: CreateRestaurantProductInput!) {
    createRestaurantProduct(input: $input) {
      id
      restaurant_id
      product_id
      local_price
      is_available
      estimated_preparation_time_min
    }
  }
`

const UPDATE_RESTAURANT_PRODUCT_MUTATION = `
  mutation UpdateRestaurantProduct($id: ID!, $input: UpdateRestaurantProductInput!) {
    updateRestaurantProduct(id: $id, input: $input) {
      id
      restaurant_id
      product_id
      local_price
      is_available
      estimated_preparation_time_min
    }
  }
`

const SET_RESTAURANT_PRODUCT_AVAILABILITY_MUTATION = `
  mutation SetRestaurantProductAvailability($id: ID!, $isAvailable: Boolean!) {
    setRestaurantProductAvailability(id: $id, is_available: $isAvailable) {
      id
      is_available
    }
  }
`

const CLIENT_NOTIFICATIONS_QUERY = `
  query GetNotificationsByUserId($userId: ID!, $unreadOnly: Boolean!, $page: Int!, $perPage: Int!) {
    getNotificationsByUserId(user_id: $userId, unread_only: $unreadOnly, page: $page, per_page: $perPage) {
      items {
        id
        type
        title
        message
        sent_at
        read_at
      }
      current_page
      per_page
      total
      last_page
    }
  }
`

const MARK_NOTIFICATION_READ_MUTATION = `
  mutation MarkNotificationAsRead($userId: ID!, $notificationId: ID!) {
    markNotificationAsRead(user_id: $userId, notification_id: $notificationId) {
      ok
      notification_id
      read_at
    }
  }
`

const MARK_ALL_NOTIFICATIONS_READ_MUTATION = `
  mutation MarkAllNotificationsAsRead($userId: ID!) {
    markAllNotificationsAsRead(user_id: $userId) {
      ok
      affected_count
    }
  }
`

export async function bootstrapRestaurantSession({
  email,
  password,
  restaurant,
  restaurantId = '',
  token,
}) {
  const trimmedEmail = email.trim()
  const trimmedPassword = password.trim()
  const trimmedRestaurantId = String(restaurantId ?? '').trim()
  const trimmedToken = token.trim()

  if (!trimmedEmail || !trimmedPassword) {
    throw new Error('Preencha o email e a palavra-passe.')
  }

  const userData = await graphqlRequest({
    query: LOGIN_USER_MUTATION,
    variables: {
      email: trimmedEmail,
      password: trimmedPassword,
    },
  })

  if (!userData.authenticateByCredentials) {
    throw new Error('Não foi possível autenticar o utilizador.')
  }

  const authenticatedUser = userData.authenticateByCredentials
  const requestSession = {
    devUserId: authenticatedUser.id,
    token: trimmedToken,
  }
  const operatorName = authenticatedUser.name || trimmedEmail.split('@')[0] || 'manager'

  const [managedRestaurantsData, managerChainData] = await Promise.all([
    graphqlRequest({
      query: RESTAURANTS_BY_MANAGER_USER_QUERY,
      variables: { userId: authenticatedUser.id },
      ...requestOptions(requestSession),
    }),
    graphqlRequest({
      query: CHAIN_BY_MANAGER_USER_QUERY,
      variables: { userId: authenticatedUser.id },
      ...requestOptions(requestSession),
    }),
  ])

  const managedRestaurants = managedRestaurantsData.getRestaurantsByManagerUserId ?? []
  const managerChain = managerChainData.getRestaurantChainByManagerUserId
  const resolvedRestaurant = trimmedRestaurantId
    ? managedRestaurants.find((entry) => entry.id === trimmedRestaurantId)
    : managedRestaurants[0]
  const isChainManager = Boolean(managerChain?.id)

  if (trimmedRestaurantId && !resolvedRestaurant) {
    throw new Error('Não tem acesso a esse restaurante.')
  }

  if (!resolvedRestaurant?.id) {
    return {
      needsSetup: true,
      operatorName,
      user: {
        id: authenticatedUser.id,
        name: authenticatedUser.name,
        email: authenticatedUser.email ?? trimmedEmail,
        isChainManager,
        chainId: managerChain?.id ?? null,
      },
      devUserId: authenticatedUser.id,
      token: trimmedToken,
    }
  }

  return {
    operatorName,
    restaurant: resolvedRestaurant.name || restaurant || 'Unidade',
    restaurantId: resolvedRestaurant.id,
    chainId: resolvedRestaurant.chain_id ?? null,
    userId: authenticatedUser.id,
    devUserId: authenticatedUser.id,
    token: trimmedToken,
    isChainManager,
  }
}

export async function refreshRestaurantSessionAccess(session) {
  const userId = session?.userId || session?.devUserId

  if (!userId) {
    return session
  }

  const requestSession = {
    devUserId: session?.devUserId || userId,
    token: session?.token ?? '',
  }

  const [managedRestaurantsData, managerChainData] = await Promise.all([
    graphqlRequest({
      query: RESTAURANTS_BY_MANAGER_USER_QUERY,
      variables: { userId },
      ...requestOptions(requestSession),
    }),
    graphqlRequest({
      query: CHAIN_BY_MANAGER_USER_QUERY,
      variables: { userId },
      ...requestOptions(requestSession),
    }),
  ])

  const managedRestaurants = managedRestaurantsData.getRestaurantsByManagerUserId ?? []
  const managerChain = managerChainData.getRestaurantChainByManagerUserId
  const isChainManager = Boolean(managerChain?.id)
  const resolvedRestaurant = isChainManager
    ? managedRestaurants.find((entry) => entry.id === session?.restaurantId) ?? managedRestaurants[0]
    : managedRestaurants[0]

  if (!resolvedRestaurant?.id) {
    throw new Error('Não tem nenhum restaurante associado.')
  }

  return {
    ...session,
    restaurant: resolvedRestaurant.name || session?.restaurant || 'Unidade',
    restaurantId: resolvedRestaurant.id,
    chainId: resolvedRestaurant.chain_id ?? managerChain?.id ?? null,
    userId,
    devUserId: requestSession.devUserId,
    isChainManager,
  }
}

function assertRestaurantAccess(session, restaurantId) {
  const requestedRestaurantId = String(restaurantId ?? session?.restaurantId ?? '').trim()

  if (!requestedRestaurantId) {
    throw new Error('Não foi encontrada uma unidade associada à sessão.')
  }

  if (!session?.isChainManager && requestedRestaurantId !== session?.restaurantId) {
    throw new Error('Gestores locais só podem aceder ao seu restaurante.')
  }

  return requestedRestaurantId
}

export async function registerRestaurantUser({
  name,
  email,
  password,
}) {
  const trimmedName = String(name ?? '').trim()
  const trimmedEmail = String(email ?? '').trim()
  const trimmedPassword = String(password ?? '').trim()

  if (!trimmedName || !trimmedEmail || !trimmedPassword) {
    throw new Error('Preencha o nome, o email e a palavra-passe.')
  }

  const data = await graphqlRequest({
    query: CREATE_RESTAURANT_USER_MUTATION,
    variables: {
      input: {
        name: trimmedName,
        email: trimmedEmail,
        password: trimmedPassword,
      },
    },
  })

  return data.createUser
}

export async function searchRestaurantChains({ q = '', pageSize = 20 } = {}) {
  const data = await graphqlRequest({
    query: SEARCH_RESTAURANT_CHAINS_QUERY,
    variables: {
      input: {
        q: q.trim(),
        pageNumber: 1,
        pageSize,
      },
    },
  })

  return data.searchRestaurantChains ?? []
}

export async function completeRestaurantOnboarding({
  user,
  mode,
  chainId,
  chainName,
  restaurant,
  token = '',
}) {
  const userId = user?.id
  if (!userId) {
    throw new Error('Utilizador inválido para configuração.')
  }

  const selectedMode = mode === 'new-chain' ? 'new-chain' : 'existing-chain'
  let resolvedChain

  if (selectedMode === 'new-chain') {
    const trimmedChainName = String(chainName ?? '').trim()
    if (!trimmedChainName) {
      throw new Error('Indique o nome da cadeia.')
    }

    const chainData = await graphqlRequest({
      query: CREATE_RESTAURANT_CHAIN_MUTATION,
      variables: {
        input: { name: trimmedChainName },
      },
    })
    resolvedChain = chainData.createRestaurantChain
  } else {
    const trimmedChainId = String(chainId ?? '').trim()
    if (!trimmedChainId) {
      throw new Error('Escolha uma cadeia existente.')
    }
    resolvedChain = { id: trimmedChainId, name: '' }
  }

  const payload = {
    chain_id: resolvedChain.id,
    name: String(restaurant?.name ?? '').trim(),
    opening_hours: String(restaurant?.opening_hours ?? '').trim(),
    closing_hours: String(restaurant?.closing_hours ?? '').trim(),
    delivery_radius: Number(restaurant?.delivery_radius ?? 0),
    street: String(restaurant?.street ?? '').trim(),
    city: String(restaurant?.city ?? '').trim(),
    postal_code: String(restaurant?.postal_code ?? '').trim(),
    country: String(restaurant?.country ?? '').trim(),
    latitude: Number(restaurant?.latitude ?? 0),
    longitude: Number(restaurant?.longitude ?? 0),
  }

  if (!payload.name || !payload.opening_hours || !payload.closing_hours) {
    throw new Error('Preencha o nome e o horário do restaurante.')
  }

  const restaurantData = await graphqlRequest({
    query: CREATE_RESTAURANT_MUTATION,
    variables: {
      input: payload,
    },
  })
  const createdRestaurant = restaurantData.createRestaurant

  const isChainManager = Boolean(user.isChainManager) || selectedMode === 'new-chain'

  if (isChainManager) {
    await graphqlRequest({
      query: ASSIGN_CHAIN_MANAGER_MUTATION,
      variables: {
        userId,
        chainId: resolvedChain.id,
      },
    })
  } else {
    await graphqlRequest({
      query: ASSIGN_LOCAL_MANAGER_MUTATION,
      variables: {
        userId,
        restaurantId: createdRestaurant.id,
      },
    })
  }

  return {
    operatorName: user.name,
    restaurant: createdRestaurant.name,
    restaurantId: createdRestaurant.id,
    chainId: createdRestaurant.chain_id,
    userId,
    devUserId: userId,
    token: String(token ?? '').trim(),
    isChainManager,
  }
}

export async function fetchRestaurantOrderDetail({ session, orderId }) {
  if (!orderId) return null
  const data = await graphqlRequest({
    query: RESTAURANT_ORDER_DETAIL_QUERY,
    variables: {
      restaurantId: session.restaurantId,
      orderId,
    },
    ...requestOptions(session),
  })
  return data.getRestaurantOrder ?? null
}

export async function fetchRestaurantOrdersHistory({
  session,
  statuses = null,
  page = 1,
  perPage = 30,
}) {
  const data = await graphqlRequest({
    query: RESTAURANT_ORDERS_HISTORY_QUERY,
    variables: {
      restaurantId: session.restaurantId,
      statuses,
      page,
      perPage,
    },
    ...requestOptions(session),
  })
  return (data.getRestaurantOrders ?? []).map(mapOrder)
}

export async function fetchRestaurantActiveOrders(session) {
  const data = await graphqlRequest({
    query: RESTAURANT_ACTIVE_ORDERS_QUERY,
    variables: {
      restaurantId: session.restaurantId,
    },
    ...requestOptions(session),
  })

  return (data.getActiveRestaurantOrders ?? []).map(mapOrder)
}

export async function acceptRestaurantOrder({ session, orderId }) {
  const data = await graphqlRequest({
    query: ACCEPT_RESTAURANT_ORDER_MUTATION,
    variables: {
      input: {
        order_id: orderId,
      },
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.acceptOrderByRestaurant.id,
    status: data.acceptOrderByRestaurant.status,
  }
}

export async function rejectRestaurantOrder({ session, orderId, reason = null }) {
  const data = await graphqlRequest({
    query: REJECT_RESTAURANT_ORDER_MUTATION,
    variables: {
      input: {
        order_id: orderId,
        reason: reason && String(reason).trim() !== '' ? String(reason).trim() : null,
      },
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.rejectOrderByRestaurant.id,
    status: data.rejectOrderByRestaurant.status,
  }
}

export async function startPreparingRestaurantOrder({ session, orderId }) {
  const data = await graphqlRequest({
    query: START_PREPARING_ORDER_MUTATION,
    variables: {
      input: {
        order_id: orderId,
      },
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.startPreparingOrder.id,
    status: data.startPreparingOrder.status,
  }
}

export async function markRestaurantOrderReady({ session, orderId }) {
  const data = await graphqlRequest({
    query: MARK_ORDER_READY_MUTATION,
    variables: {
      input: {
        order_id: orderId,
      },
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    order_id: data.markOrderReady.id,
    status: data.markOrderReady.status,
  }
}

export async function updateOrderItemStatus({ session, orderItemId, status }) {
  const data = await graphqlRequest({
    query: UPDATE_ORDER_ITEM_STATUS_MUTATION,
    variables: {
      input: {
        order_item_id: orderItemId,
        status,
      },
    },
    ...requestOptions(session),
  })

  const item = data.updateOrderItemStatus.items?.find((entry) => entry.id === orderItemId)
  return {
    ok: true,
    order_item_id: orderItemId,
    order_id: data.updateOrderItemStatus.id,
    order_item_status: item?.status ?? status,
    order_status: data.updateOrderItemStatus.status,
  }
}

export async function fetchRestaurantMenuProducts(session) {
  if (!session?.restaurantId) {
    throw new Error('Define Restaurant ID no login para gerir o menu.')
  }

  const data = await graphqlRequest({
    query: RESTAURANT_MENU_QUERY,
    variables: {
      restaurantId: session.restaurantId,
    },
    ...requestOptions(session),
  })

  return (data.getRestaurantMenu?.products ?? []).map((item) =>
    mapRestaurantProduct(item, data.getRestaurantMenu?.categories ?? []),
  )
}

async function resolveCategoryId({ session, chainId, categoryName }) {
  const categoryData = await graphqlRequest({
    query: CHAIN_CATEGORIES_QUERY,
    variables: { chainId },
    ...requestOptions(session),
  })

  const existingCategory = (categoryData.getCategoriesByChainId ?? []).find(
    (category) => normalizeCategoryName(category.name) === normalizeCategoryName(categoryName),
  )

  if (existingCategory) {
    return existingCategory.id
  }

  const createdCategory = await graphqlRequest({
    query: CREATE_CATEGORY_MUTATION,
    variables: {
      input: {
        chain_id: chainId,
        name: categoryName.trim(),
      },
    },
    ...requestOptions(session),
  })

  return createdCategory.createCategory.id
}

export async function updateRestaurantMenuProduct({ session, input }) {
  const payload = {}

  if (input.price !== undefined) payload.local_price = Number(input.price)
  if (input.is_available !== undefined) payload.is_available = Boolean(input.is_available)
  if (input.estimated_preparation_time_min !== undefined) {
    payload.estimated_preparation_time_min = input.estimated_preparation_time_min
  }

  const data = await graphqlRequest({
    query: UPDATE_RESTAURANT_PRODUCT_MUTATION,
    variables: {
      id: input.restaurant_product_id,
      input: payload,
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    restaurant_product_id: data.updateRestaurantProduct.id,
    product_id: data.updateRestaurantProduct.product_id,
    restaurant_id: data.updateRestaurantProduct.restaurant_id,
    message: 'Produto atualizado.',
  }
}

export async function fetchChainCatalog({ session, chainId }) {
  const resolvedChainId = chainId ?? session?.chainId
  if (!resolvedChainId) {
    throw new Error('Não foi encontrada uma cadeia associada à sessão.')
  }

  const data = await graphqlRequest({
    query: CHAIN_CATALOG_QUERY,
    variables: { chainId: resolvedChainId },
    ...requestOptions(session),
  })

  const categories = data.getCategoriesByChainId ?? []
  const products = categories.flatMap((category) =>
    (category.products ?? []).map((product) => ({
      id: product.id,
      name: product.name,
      price: Number(product.price ?? 0),
      description: product.description ?? '',
      category_id: category.id,
      category_name: category.name,
    })),
  )

  return {
    categories: categories.map((category) => ({ id: category.id, name: category.name })),
    products,
  }
}

export async function createChainProduct({ session, input }) {
  if (!session?.chainId) {
    throw new Error('Não foi encontrada uma cadeia associada à sessão.')
  }

  const categoryId = await resolveCategoryId({
    session,
    chainId: session.chainId,
    categoryName: input.category,
  })

  const data = await graphqlRequest({
    query: CREATE_PRODUCT_MUTATION,
    variables: {
      input: {
        category_id: categoryId,
        name: input.name,
        price: Number(input.price),
        description: input.description ?? null,
        option_groups: Array.isArray(input.option_groups)
          ? input.option_groups.map((group) => ({
              name: group.name,
              min_options: Number(group.min_options ?? 0),
              max_options: Number(group.max_options ?? 1),
              options: (group.options ?? []).map((option) => ({
                name: option.name,
                extra_price: Number(option.extra_price ?? 0),
                default_option: Boolean(option.default_option),
              })),
            }))
          : [],
      },
    },
    ...requestOptions(session),
  })

  return data.createProduct
}

export async function updateChainProduct({ session, productId, input }) {
  const payload = {}

  if (input.name !== undefined) payload.name = input.name
  if (input.description !== undefined) payload.description = input.description
  if (input.price !== undefined) payload.price = Number(input.price)
  if (input.option_groups !== undefined) {
    payload.option_groups = input.option_groups.map((group) => ({
      id: group.id ?? null,
      name: group.name,
      min_options: Number(group.min_options ?? 0),
      max_options: Number(group.max_options ?? 1),
      options: (group.options ?? []).map((option) => ({
        id: option.id ?? null,
        name: option.name,
        extra_price: Number(option.extra_price ?? 0),
        default_option: Boolean(option.default_option),
      })),
    }))
  }

  const data = await graphqlRequest({
    query: UPDATE_PRODUCT_MUTATION,
    variables: { id: productId, input: payload },
    ...requestOptions(session),
  })

  return data.updateProduct
}

export async function deleteChainProduct({ session, productId }) {
  const data = await graphqlRequest({
    query: DELETE_PRODUCT_MUTATION,
    variables: { id: productId },
    ...requestOptions(session),
  })

  return { ok: Boolean(data.deleteProduct) }
}

export async function addChainProductToRestaurantMenu({
  session,
  productId,
  localPrice,
  estimatedPreparationTimeMin,
  isAvailable = true,
}) {
  if (!session?.restaurantId) {
    throw new Error('Não foi encontrada uma unidade associada à sessão.')
  }

  const data = await graphqlRequest({
    query: CREATE_RESTAURANT_PRODUCT_MUTATION,
    variables: {
      input: {
        restaurant_id: session.restaurantId,
        product_id: productId,
        local_price: localPrice === undefined || localPrice === null || localPrice === '' ? null : Number(localPrice),
        is_available: Boolean(isAvailable),
        estimated_preparation_time_min:
          estimatedPreparationTimeMin === undefined ||
          estimatedPreparationTimeMin === null ||
          estimatedPreparationTimeMin === ''
            ? null
            : Number(estimatedPreparationTimeMin),
      },
    },
    ...requestOptions(session),
  })

  return data.createRestaurantProduct
}

export async function deleteRestaurantMenuProduct({ session, restaurantProductId }) {
  await graphqlRequest({
    query: SET_RESTAURANT_PRODUCT_AVAILABILITY_MUTATION,
    variables: {
      id: restaurantProductId,
      isAvailable: false,
    },
    ...requestOptions(session),
  })

  return {
    ok: true,
    restaurant_product_id: restaurantProductId,
    message: 'Produto desativado.',
  }
}

export async function fetchChainPromotions({ session, chainId }) {
  const data = await graphqlRequest({
    query: CHAIN_PROMOTIONS_QUERY,
    variables: { chainId: chainId ?? session.chainId },
    ...requestOptions(session),
  })
  return data.getPromotionsByChainId ?? []
}

export async function fetchChainCoupons({ session, chainId }) {
  const data = await graphqlRequest({
    query: CHAIN_COUPONS_QUERY,
    variables: { chainId: chainId ?? session.chainId },
    ...requestOptions(session),
  })
  return data.getCouponsByChainId ?? []
}

export async function createChainPromotion({ session, input }) {
  const data = await graphqlRequest({
    query: CREATE_PROMOTION_MUTATION,
    variables: {
      input: {
        chain_id: session.chainId,
        name: input.name,
        description: input.description ?? null,
        type: input.type,
        target: input.target,
        discount: Number(input.discount),
        start_date: input.start_date ?? null,
        end_date: input.end_date ?? null,
        items: input.items ?? [],
      },
    },
    ...requestOptions(session),
  })
  return data.createPromotion
}

export async function updateChainPromotion({ session, promotionId, input }) {
  const data = await graphqlRequest({
    query: UPDATE_PROMOTION_MUTATION,
    variables: {
      id: promotionId,
      input: {
        name: input.name,
        description: input.description ?? null,
        type: input.type,
        target: input.target,
        discount: Number(input.discount),
        start_date: input.start_date ?? null,
        end_date: input.end_date ?? null,
        items: input.items ?? [],
      },
    },
    ...requestOptions(session),
  })
  return data.updatePromotion
}

export async function updateChainCoupon({ session, couponId, input }) {
  const data = await graphqlRequest({
    query: UPDATE_COUPON_MUTATION,
    variables: {
      id: couponId,
      input: {
        code: input.code,
        description: input.description ?? null,
        type: input.type,
        target: input.target,
        discount: Number(input.discount),
        expiry_date: input.expiry_date ?? null,
        items: input.items ?? [],
      },
    },
    ...requestOptions(session),
  })
  return data.updateCoupon
}

export async function fetchChainProductsAndCategories({ session, chainId }) {
  const data = await graphqlRequest({
    query: CHAIN_PRODUCTS_QUERY,
    variables: { chainId: chainId ?? session.chainId },
    ...requestOptions(session),
  })
  const categories = (data.getCategoriesByChainId ?? []).map((cat) => ({ id: cat.id, name: cat.name }))
  const products = (data.getCategoriesByChainId ?? []).flatMap((cat) =>
    (cat.products ?? []).map((product) => ({
      id: product.id,
      name: product.name,
      category_id: cat.id,
      category_name: cat.name,
    })),
  )
  return { categories, products }
}

export async function deleteChainPromotion({ session, promotionId }) {
  const data = await graphqlRequest({
    query: DELETE_PROMOTION_MUTATION,
    variables: { id: promotionId },
    ...requestOptions(session),
  })
  return { ok: Boolean(data.deletePromotion) }
}

export async function createChainCoupon({ session, input }) {
  const data = await graphqlRequest({
    query: CREATE_COUPON_MUTATION,
    variables: {
      input: {
        chain_id: session.chainId,
        code: input.code,
        description: input.description ?? null,
        type: input.type,
        target: input.target,
        discount: Number(input.discount),
        expiry_date: input.expiry_date ?? null,
        items: input.items ?? [],
      },
    },
    ...requestOptions(session),
  })
  return data.createCoupon
}

export async function deleteChainCoupon({ session, couponId }) {
  const data = await graphqlRequest({
    query: DELETE_COUPON_MUTATION,
    variables: { id: couponId },
    ...requestOptions(session),
  })
  return { ok: Boolean(data.deleteCoupon) }
}

export async function fetchRestaurantReviews({ session, restaurantId, limit = 30 }) {
  const data = await graphqlRequest({
    query: TARGET_REVIEWS_QUERY,
    variables: {
      targetType: 'RESTAURANT',
      targetId: restaurantId ?? session.restaurantId,
      perPage: limit,
    },
    ...requestOptions(session),
  })
  return data.getReviewsByTarget ?? []
}

export async function fetchChainCategories({ session, chainId }) {
  const data = await graphqlRequest({
    query: CHAIN_CATEGORIES_QUERY,
    variables: { chainId: chainId ?? session.chainId },
    ...requestOptions(session),
  })
  return data.getCategoriesByChainId ?? []
}

export async function createChainCategory({ session, chainId, name }) {
  const data = await graphqlRequest({
    query: CREATE_CATEGORY_MUTATION,
    variables: {
      input: { chain_id: chainId ?? session.chainId, name: name.trim() },
    },
    ...requestOptions(session),
  })
  return data.createCategory
}

export async function updateChainCategory({ session, categoryId, name }) {
  const data = await graphqlRequest({
    query: UPDATE_CATEGORY_MUTATION,
    variables: { id: categoryId, input: { name: name.trim() } },
    ...requestOptions(session),
  })
  return data.updateCategory
}

export async function deleteChainCategory({ session, categoryId }) {
  const data = await graphqlRequest({
    query: DELETE_CATEGORY_MUTATION,
    variables: { id: categoryId },
    ...requestOptions(session),
  })
  return { ok: Boolean(data.deleteCategory) }
}

export async function fetchRestaurantProfile({ session, restaurantId }) {
  const resolvedRestaurantId = assertRestaurantAccess(session, restaurantId)
  const data = await graphqlRequest({
    query: RESTAURANT_QUERY,
    variables: { id: resolvedRestaurantId },
    ...requestOptions(session),
  })

  return data.getRestaurantById ?? null
}

export async function fetchRestaurantChainProfile({ session, chainId }) {
  const data = await graphqlRequest({
    query: RESTAURANT_CHAIN_QUERY,
    variables: { id: chainId ?? session.chainId },
    ...requestOptions(session),
  })

  return data.getRestaurantChainById ?? null
}

export async function fetchChainRestaurants({ session, chainId }) {
  if (!session?.isChainManager) {
    return []
  }

  const data = await graphqlRequest({
    query: CHAIN_RESTAURANTS_QUERY,
    variables: { chainId: chainId ?? session.chainId },
    ...requestOptions(session),
  })

  return data.getRestaurantsByChainId ?? []
}

export async function updateRestaurantProfile({ session, restaurantId, input }) {
  const resolvedRestaurantId = assertRestaurantAccess(session, restaurantId)
  const payload = {
    name: input.name?.trim(),
    opening_hours: input.opening_hours?.trim(),
    closing_hours: input.closing_hours?.trim(),
    delivery_radius: Number(input.delivery_radius),
    street: input.street?.trim(),
    city: input.city?.trim(),
    postal_code: input.postal_code?.trim(),
    country: input.country?.trim(),
    latitude: Number(input.latitude),
    longitude: Number(input.longitude),
  }

  const data = await graphqlRequest({
    query: UPDATE_RESTAURANT_MUTATION,
    variables: { id: resolvedRestaurantId, input: payload },
    ...requestOptions(session),
  })

  return data.updateRestaurant
}

export async function updateRestaurantChainProfile({ session, chainId, name }) {
  const data = await graphqlRequest({
    query: UPDATE_RESTAURANT_CHAIN_MUTATION,
    variables: { id: chainId ?? session.chainId, input: { name: name.trim() } },
    ...requestOptions(session),
  })

  return data.updateRestaurantChain
}

export async function fetchProductOptionGroupsAdmin({ session, productId }) {
  const data = await graphqlRequest({
    query: PRODUCT_OPTION_GROUPS_QUERY_ADMIN,
    variables: { productId },
    ...requestOptions(session),
  })
  return (data.getProductOptionGroups ?? []).map((group) => ({
    id: group.id,
    name: group.name,
    min_options: Number(group.min_options ?? 0),
    max_options: Number(group.max_options ?? 1),
    options: (group.options ?? []).map((option) => ({
      id: option.id,
      name: option.name,
      extra_price: Number(option.extra_price ?? 0),
      default_option: Boolean(option.default_option),
    })),
  }))
}

export async function fetchOperatorNotifications({
  session,
  unreadOnly = false,
  page = 1,
  perPage = 50,
}) {
  const data = await graphqlRequest({
    query: CLIENT_NOTIFICATIONS_QUERY,
    variables: {
      userId: currentUserId(session),
      unreadOnly,
      page,
      perPage,
    },
    ...requestOptions(session),
  })

  const pageData = data.getNotificationsByUserId ?? {}

  return {
    ...pageData,
    items: (pageData.items ?? []).map(mapNotification),
  }
}

export async function markOperatorNotificationRead({ session, notificationId }) {
  const data = await graphqlRequest({
    query: MARK_NOTIFICATION_READ_MUTATION,
    variables: {
      userId: currentUserId(session),
      notificationId,
    },
    ...requestOptions(session),
  })

  return data.markNotificationAsRead
}

export async function markAllOperatorNotificationsRead({ session }) {
  const data = await graphqlRequest({
    query: MARK_ALL_NOTIFICATIONS_READ_MUTATION,
    variables: {
      userId: currentUserId(session),
    },
    ...requestOptions(session),
  })

  return data.markAllNotificationsAsRead
}
