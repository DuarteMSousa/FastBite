import { graphqlRequest } from '../apiClient'
import { requestOptions, sessionUserId } from './core'

const CREATE_REVIEW_MUTATION = `
  mutation CreateReview($input: CreateReviewInput!) {
    createReview(input: $input) {
      id
      rating
      comment
      target_type
      target_id
      created_at
    }
  }
`

const UPDATE_REVIEW_MUTATION = `
  mutation UpdateReview($userId: ID!, $reviewId: ID!, $input: UpdateReviewInput!) {
    updateReview(user_id: $userId, review_id: $reviewId, input: $input) {
      id
      rating
      comment
    }
  }
`

const DELETE_REVIEW_MUTATION = `
  mutation DeleteReview($userId: ID!, $reviewId: ID!) {
    deleteReview(user_id: $userId, review_id: $reviewId)
  }
`

const CLIENT_REVIEWS_QUERY = `
  query ClientReviews($userId: ID!, $perPage: Int) {
    getReviewsByUserId(user_id: $userId, per_page: $perPage) {
      id
      rating
      comment
      target_type
      target_id
      created_at
    }
  }
`

export async function createClientReview({ session, rating, comment = null, targetType, targetId }) {
  if (rating < 1 || rating > 5) {
    throw new Error('A avaliacao deve estar entre 1 e 5.')
  }

  const data = await graphqlRequest({
    query: CREATE_REVIEW_MUTATION,
    variables: {
      input: {
        user_id: sessionUserId(session),
        rating,
        comment: comment && comment.trim() !== '' ? comment.trim() : null,
        target_type: targetType,
        target_id: targetId,
      },
    },
    ...requestOptions(session),
  })

  return data.createReview
}

export async function updateClientReview({ session, reviewId, rating, comment }) {
  const data = await graphqlRequest({
    query: UPDATE_REVIEW_MUTATION,
    variables: {
      userId: sessionUserId(session),
      reviewId,
      input: {
        rating,
        comment: comment && comment.trim() !== '' ? comment.trim() : null,
      },
    },
    ...requestOptions(session),
  })
  return data.updateReview
}

export async function deleteClientReview({ session, reviewId }) {
  const data = await graphqlRequest({
    query: DELETE_REVIEW_MUTATION,
    variables: {
      userId: sessionUserId(session),
      reviewId,
    },
    ...requestOptions(session),
  })
  return { ok: Boolean(data.deleteReview) }
}

export async function fetchClientReviewsHistory({ session, limit = 50 } = {}) {
  const data = await graphqlRequest({
    query: CLIENT_REVIEWS_QUERY,
    variables: {
      userId: sessionUserId(session),
      perPage: limit,
    },
    ...requestOptions(session),
  })
  return data.getReviewsByUserId ?? []
}
