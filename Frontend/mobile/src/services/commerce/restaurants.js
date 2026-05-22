import { graphqlRequest } from '../apiClient'
import { requestOptions } from './core'

function mapRestaurant(restaurant) {
  const ratingCount = Number(restaurant?.rating_count ?? 0)
  return {
    id: restaurant.id,
    name: restaurant.name,
    city: restaurant.address?.city ?? '',
    country: restaurant.address?.country ?? '',
    postal_code: restaurant.address?.postal_code ?? '',
    rating: ratingCount > 0 ? Number(restaurant.rating_sum ?? 0) / ratingCount : 0,
    rating_count: ratingCount,
  }
}

function mapMenuProduct(item, categories = []) {
  const category = categories.find((entry) => entry.id === item.product?.category_id)
  return {
    restaurant_product_id: item.id,
    product_id: item.product_id,
    category: category?.name ?? '',
    name: item.product?.name ?? 'Produto',
    description: item.product?.description ?? '',
    price: item.local_price ?? item.product?.price ?? 0,
    is_available: Boolean(item.is_available),
    estimated_preparation_time_min: item.estimated_preparation_time_min,
  }
}

const RESTAURANTS_QUERY = `
  query Restaurants($input: SearchRestaurantsInput) {
    searchRestaurants(input: $input) {
      id
      name
      rating_sum
      rating_count
      address { city country postal_code }
    }
  }
`

const PRODUCT_OPTION_GROUPS_QUERY = `
  query ProductOptionGroups($productId: ID!) {
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

const RESTAURANT_MENU_QUERY = `
  query RestaurantMenu($restaurantId: ID!) {
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

export async function fetchRestaurants(session, filters = {}) {
  const input = {}
  const stringFields = ['q', 'name', 'chainName', 'city', 'country', 'postalCode']
  for (const field of stringFields) {
    const value = String(filters[field] ?? '').trim()
    if (value !== '') input[field] = value
  }
  if (filters.pageNumber) input.pageNumber = filters.pageNumber
  if (filters.pageSize) input.pageSize = filters.pageSize

  const data = await graphqlRequest({
    query: RESTAURANTS_QUERY,
    variables: { input: Object.keys(input).length > 0 ? input : null },
    ...requestOptions(session),
  })

  return (data.searchRestaurants ?? []).map(mapRestaurant)
}

export async function fetchProductOptionGroups({ session, productId }) {
  const data = await graphqlRequest({
    query: PRODUCT_OPTION_GROUPS_QUERY,
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

export async function fetchRestaurantMenu({ session, restaurantId }) {
  const data = await graphqlRequest({
    query: RESTAURANT_MENU_QUERY,
    variables: { restaurantId },
    ...requestOptions(session),
  })

  return (data.getRestaurantMenu?.products ?? [])
    .map((item) => mapMenuProduct(item, data.getRestaurantMenu?.categories ?? []))
    .filter((item) => item.is_available)
}
