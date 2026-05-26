import { useCallback, useEffect, useMemo, useState } from 'react'
import { fetchRestaurantReviews } from '../../../services/restaurantOpsService'

function renderStars(rating) {
  const filled = Math.max(0, Math.min(5, Math.round(rating)))
  return '★'.repeat(filled) + '☆'.repeat(5 - filled)
}

export function RestaurantReviewsScreen({ session }) {
  const [reviews, setReviews] = useState([])
  const [loading, setLoading] = useState(false)
  const [errorText, setErrorText] = useState('')
  const [filter, setFilter] = useState(0)

  const load = useCallback(async () => {
    if (!session?.restaurantId) {
      setErrorText('Não foi encontrada uma unidade associada à sessão.')
      return
    }
    try {
      setLoading(true)
      const data = await fetchRestaurantReviews({ session, limit: 80 })
      setReviews(data)
      setErrorText('')
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoading(false)
    }
  }, [session])

  useEffect(() => {
    queueMicrotask(() => load())
  }, [load])

  const stats = useMemo(() => {
    if (reviews.length === 0) {
      return { average: 0, total: 0, distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 } }
    }
    const distribution = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
    let total = 0
    reviews.forEach((review) => {
      total += Number(review.rating ?? 0)
      const r = Number(review.rating)
      if (distribution[r] !== undefined) distribution[r] += 1
    })
    return {
      average: total / reviews.length,
      total: reviews.length,
      distribution,
    }
  }, [reviews])

  const visibleReviews = useMemo(() => {
    if (filter === 0) return reviews
    return reviews.filter((review) => Number(review.rating) === filter)
  }, [reviews, filter])

  return (
    <section className="rb-page">
      <header className="rb-page-head rb-page-head-row">
        <div>
          <h2>Avaliações do restaurante</h2>
          <p>Feedback recebido dos clientes</p>
        </div>
        <button type="button" className="rb-btn-outline" onClick={load} disabled={loading}>
          {loading ? 'A carregar...' : 'Atualizar'}
        </button>
      </header>

      <div className="rb-stat-grid">
        <article className="rb-stat-card">
          <p className="rb-stat-label">Média</p>
          <p className="rb-stat-value">
            {stats.total > 0 ? `${stats.average.toFixed(2)} / 5` : '-'}
          </p>
        </article>
        <article className="rb-stat-card">
          <p className="rb-stat-label">Total de avaliações</p>
          <p className="rb-stat-value">{stats.total}</p>
        </article>
        {[5, 4, 3, 2, 1].slice(0, 2).map((star) => (
          <article className="rb-stat-card" key={`stat-${star}`}>
            <p className="rb-stat-label">{star} estrelas</p>
            <p className="rb-stat-value">{stats.distribution[star] ?? 0}</p>
          </article>
        ))}
      </div>

      <article className="rb-search-wrap">
        <div className="rb-filter-row">
          <button
            type="button"
            className={`rb-filter ${filter === 0 ? 'active' : ''}`}
            onClick={() => setFilter(0)}
          >
            Todas
          </button>
          {[5, 4, 3, 2, 1].map((star) => (
            <button
              key={star}
              type="button"
              className={`rb-filter ${filter === star ? 'active' : ''}`}
              onClick={() => setFilter(star)}
            >
              {star} estrelas ({stats.distribution[star] ?? 0})
            </button>
          ))}
        </div>
      </article>

      <article className="rb-table-card">
        <div className="rb-table-head">
          <h3>Comentários</h3>
        </div>
        {visibleReviews.length === 0 && !loading ? (
          <div className="rb-empty-state rb-empty-state-inline">
            <h3>Sem avaliações para mostrar.</h3>
          </div>
        ) : null}
        {visibleReviews.map((review) => (
          <div key={review.id} className="rb-review-card">
            <div className="rb-review-head">
              <strong>{renderStars(review.rating)}</strong>
              <small>{review.created_at ? new Date(review.created_at).toLocaleString() : '-'}</small>
            </div>
            <p>{review.comment || 'Sem comentário.'}</p>
          </div>
        ))}
      </article>

      {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
    </section>
  )
}
