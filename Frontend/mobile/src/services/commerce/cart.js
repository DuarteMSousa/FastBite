import { graphqlRequest } from '../apiClient'
import { mapCart, requestOptions, sessionUserId } from './core'

const CART_QUERY = `
  query ClientCart($userId: ID!) {
    getCartByUserId(user_id: $userId) {
      id
      total
      items {
        id
        restaurant_product_id
        quantity
        unit_price
        total_price
        restaurantProduct {
          restaurant_id
          product { name }
          restaurant { name }
        }
      }
    }
  }
`

const ADD_CART_ITEM_MUTATION = `
  mutation AddCartItem($input: AddCartItemInput!) {
    addCartItem(input: $input) {
      id
      total
      items {
        id
        restaurant_product_id
        quantity
        unit_price
        total_price
        restaurantProduct {
          restaurant_id
          product { name }
          restaurant { name }
        }
      }
    }
  }
`

const UPDATE_CART_ITEM_MUTATION = `
  mutation UpdateCartItem($cartItemId: ID!, $input: UpdateCartItemInput!) {
    updateCartItem(cart_item_id: $cartItemId, input: $input) {
      id
      total
      items {
        id
        restaurant_product_id
        quantity
        unit_price
        total_price
        restaurantProduct {
          restaurant_id
          product { name }
          restaurant { name }
        }
      }
    }
  }
`

const REMOVE_CART_ITEM_MUTATION = `
  mutation RemoveCartItem($userId: ID!, $cartItemId: ID!) {
    removeCartItem(user_id: $userId, cart_item_id: $cartItemId) {
      id
      total
      items {
        id
        restaurant_product_id
        quantity
        unit_price
        total_price
        restaurantProduct {
          restaurant_id
          product { name }
          restaurant { name }
        }
      }
    }
  }
`

const CLEAR_CART_MUTATION = `
  mutation ClearCart($userId: ID!) {
    clearCart(user_id: $userId)
  }
`

export async function fetchMyCart(session) {
  const data = await graphqlRequest({
    query: CART_QUERY,
    variables: { userId: sessionUserId(session) },
    ...requestOptions(session),
  })

  return mapCart(data.getCartByUserId)
}

export async function addCartItem({
  session,
  restaurantProductId,
  quantity = 1,
  optionIds = [],
}) {
  const data = await graphqlRequest({
    query: ADD_CART_ITEM_MUTATION,
    variables: {
      input: {
        user_id: sessionUserId(session),
        restaurant_product_id: restaurantProductId,
        quantity,
        option_ids: optionIds,
      },
    },
    ...requestOptions(session),
  })

  return mapCart(data.addCartItem)
}

export async function updateCartItem({ session, cartItemId, quantity }) {
  const data = await graphqlRequest({
    query: UPDATE_CART_ITEM_MUTATION,
    variables: {
      cartItemId,
      input: {
        user_id: sessionUserId(session),
        quantity,
      },
    },
    ...requestOptions(session),
  })

  return mapCart(data.updateCartItem)
}

export async function removeCartItem({ session, cartItemId }) {
  const data = await graphqlRequest({
    query: REMOVE_CART_ITEM_MUTATION,
    variables: {
      userId: sessionUserId(session),
      cartItemId,
    },
    ...requestOptions(session),
  })

  return mapCart(data.removeCartItem)
}

export async function clearCart({ session }) {
  const data = await graphqlRequest({
    query: CLEAR_CART_MUTATION,
    variables: { userId: sessionUserId(session) },
    ...requestOptions(session),
  })

  return Boolean(data.clearCart)
}
