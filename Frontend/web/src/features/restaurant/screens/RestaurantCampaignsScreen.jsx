import { useCallback, useEffect, useState } from 'react'
import {
  createChainCoupon,
  createChainPromotion,
  deleteChainCoupon,
  deleteChainPromotion,
  fetchChainCoupons,
  fetchChainProductsAndCategories,
  fetchChainPromotions,
  updateChainCoupon,
  updateChainPromotion,
} from '../../../services/restaurantOpsService'
import { ConfirmDialog } from '../../../components/common/ConfirmDialog'

const DISCOUNT_TYPES = ['PERCENTAGE', 'FIXED_AMOUNT']
const DISCOUNT_TARGETS = ['ORDER', 'PRODUCT', 'DELIVERY', 'CATEGORY']
const DISCOUNT_TYPE_LABELS = {
  PERCENTAGE: 'Percentagem',
  FIXED_AMOUNT: 'Valor fixo',
}

function formatDiscount(type, discount) {
  const value = Number(discount ?? 0)
  if (type === 'PERCENTAGE') return `${value}%`
  return `${value.toFixed(2)} EUR`
}

function discountTypeLabel(type) {
  return DISCOUNT_TYPE_LABELS[type] ?? type
}

function targetLabel(target) {
  if (target === 'ORDER') return 'Encomenda inteira'
  if (target === 'PRODUCT') return 'Produtos específicos'
  if (target === 'CATEGORY') return 'Categorias específicas'
  if (target === 'DELIVERY') return 'Taxa de entrega'
  return target
}

function itemLabel(item) {
  if (item?.product?.name) return `Produto: ${item.product.name}`
  if (item?.category?.name) return `Categoria: ${item.category.name}`
  return 'Item não identificado'
}

function todayInputValue() {
  const now = new Date()
  const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
  return local.toISOString().slice(0, 10)
}

function dateInputValue(value) {
  return value ? String(value).slice(0, 10) : ''
}

function validateDiscount(draft) {
  const value = Number(draft.discount)
  if (draft.discount === '' || !Number.isFinite(value) || value <= 0) {
    return 'O desconto tem de ser maior que zero.'
  }
  if (draft.type === 'PERCENTAGE' && value > 100) {
    return 'O desconto em percentagem não pode ser maior que 100.'
  }
  return ''
}

function emptyPromotionDraft() {
  return {
    name: '',
    description: '',
    type: 'PERCENTAGE',
    target: 'ORDER',
    start_date: '',
    end_date: '',
    discount: '',
    item_ids: [],
  }
}

function emptyCouponDraft() {
  return {
    code: '',
    description: '',
    type: 'PERCENTAGE',
    target: 'ORDER',
    discount: '',
    item_ids: [],
    expiry_date: '',
  }
}

function buildItems(draft) {
  if (draft.target === 'PRODUCT' || draft.target === 'CATEGORY') {
    return (draft.item_ids ?? []).map((item_id) => ({ item_id }))
  }
  return []
}

function toggleId(list, id) {
  return list.includes(id) ? list.filter((entry) => entry !== id) : [...list, id]
}

export function RestaurantCampaignsScreen({ session }) {
  const [promotions, setPromotions] = useState([])
  const [coupons, setCoupons] = useState([])
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [errorText, setErrorText] = useState('')
  const [infoText, setInfoText] = useState('')
  const [promotionDraft, setPromotionDraft] = useState(emptyPromotionDraft())
  const [couponDraft, setCouponDraft] = useState(emptyCouponDraft())
  const [showPromotionForm, setShowPromotionForm] = useState(false)
  const [showCouponForm, setShowCouponForm] = useState(false)
  const [deletePromotionTarget, setDeletePromotionTarget] = useState(null)
  const [deleteCouponTarget, setDeleteCouponTarget] = useState(null)
  const [editingPromotionId, setEditingPromotionId] = useState('')
  const [editingCouponId, setEditingCouponId] = useState('')
  const [expandedPromotionId, setExpandedPromotionId] = useState('')
  const [expandedCouponId, setExpandedCouponId] = useState('')
  const [categories, setCategories] = useState([])
  const [products, setProducts] = useState([])

  const load = useCallback(async () => {
    if (!session?.chainId) {
      setErrorText('Sem chain_id na sessão. Esta vista exige permissões de cadeia.')
      return
    }
    try {
      setLoading(true)
      setPromotions([])
      setCoupons([])
      const [promotionsList, couponsList, catalog] = await Promise.all([
        fetchChainPromotions({ session }),
        fetchChainCoupons({ session }),
        fetchChainProductsAndCategories({ session }).catch(() => ({ categories: [], products: [] })),
      ])
      setPromotions(promotionsList)
      setCoupons(couponsList)
      setCategories(catalog.categories)
      setProducts(catalog.products)
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

  async function handleSavePromotion() {
    if (!promotionDraft.name.trim()) {
      setErrorText('Nome obrigatório.')
      return
    }
    const discountError = validateDiscount(promotionDraft)
    if (discountError) {
      setErrorText(discountError)
      return
    }
    if (promotionDraft.target === 'PRODUCT' && promotionDraft.item_ids.length === 0) {
      setErrorText('Escolhe pelo menos um produto.')
      return
    }
    if (promotionDraft.target === 'CATEGORY' && promotionDraft.item_ids.length === 0) {
      setErrorText('Escolhe pelo menos uma categoria.')
      return
    }
    if (
      promotionDraft.start_date &&
      promotionDraft.end_date &&
      promotionDraft.end_date < promotionDraft.start_date
    ) {
      setErrorText('A data de fim tem de ser posterior à data de início.')
      return
    }

    try {
      setSaving(true)
      const input = {
        name: promotionDraft.name,
        description: promotionDraft.description || null,
        type: promotionDraft.type,
        target: promotionDraft.target,
        discount: Number(promotionDraft.discount ?? 0),
        start_date: promotionDraft.start_date || null,
        end_date: promotionDraft.end_date || null,
        items: buildItems(promotionDraft),
      }
      if (editingPromotionId) {
        await updateChainPromotion({ session, promotionId: editingPromotionId, input })
        setInfoText('Promoção atualizada.')
      } else {
        await createChainPromotion({ session, input })
        setInfoText('Promoção criada.')
      }
      setPromotionDraft(emptyPromotionDraft())
      setShowPromotionForm(false)
      setEditingPromotionId('')
      await load()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  function startEditPromotion(promotion) {
    setEditingPromotionId(promotion.id)
    setPromotionDraft({
      name: promotion.name,
      description: promotion.description ?? '',
      type: promotion.type,
      target: promotion.target,
      start_date: dateInputValue(promotion.start_date),
      end_date: dateInputValue(promotion.end_date),
      discount: String(promotion.discount ?? 10),
      item_ids: (promotion.promotionItems ?? []).map((entry) => entry.item_id),
    })
    setShowPromotionForm(true)
  }

  async function handleConfirmDeletePromotion() {
    if (!deletePromotionTarget) return
    try {
      setSaving(true)
      await deleteChainPromotion({ session, promotionId: deletePromotionTarget.id })
      setInfoText('Promoção apagada.')
      setDeletePromotionTarget(null)
      await load()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function handleSaveCoupon() {
    if (!couponDraft.code.trim()) {
      setErrorText('Código de cupão obrigatório.')
      return
    }
    const discountError = validateDiscount(couponDraft)
    if (discountError) {
      setErrorText(discountError)
      return
    }
    if (couponDraft.target === 'PRODUCT' && couponDraft.item_ids.length === 0) {
      setErrorText('Escolhe pelo menos um produto.')
      return
    }
    if (couponDraft.target === 'CATEGORY' && couponDraft.item_ids.length === 0) {
      setErrorText('Escolhe pelo menos uma categoria.')
      return
    }
    if (couponDraft.expiry_date && couponDraft.expiry_date < todayInputValue()) {
      setErrorText('A validade do cupão não pode ser uma data passada.')
      return
    }
    try {
      setSaving(true)
      const input = {
        code: couponDraft.code.trim().toUpperCase(),
        description: couponDraft.description || null,
        type: couponDraft.type,
        target: couponDraft.target,
        discount: Number(couponDraft.discount ?? 0),
        expiry_date: couponDraft.expiry_date || null,
        items: buildItems(couponDraft),
      }
      if (editingCouponId) {
        await updateChainCoupon({ session, couponId: editingCouponId, input })
        setInfoText('Cupão atualizado.')
      } else {
        await createChainCoupon({ session, input })
        setInfoText('Cupão criado.')
      }
      setCouponDraft(emptyCouponDraft())
      setShowCouponForm(false)
      setEditingCouponId('')
      await load()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  function startEditCoupon(coupon) {
    setEditingCouponId(coupon.id)
    setCouponDraft({
      code: coupon.code,
      description: coupon.description ?? '',
      type: coupon.type,
      target: coupon.target,
      discount: String(coupon.discount ?? 10),
      item_ids: (coupon.promotionItems ?? []).map((entry) => entry.item_id),
      expiry_date: dateInputValue(coupon.expiry_date),
    })
    setShowCouponForm(true)
  }

  async function handleConfirmDeleteCoupon() {
    if (!deleteCouponTarget) return
    try {
      setSaving(true)
      await deleteChainCoupon({ session, couponId: deleteCouponTarget.id })
      setInfoText('Cupão apagado.')
      setDeleteCouponTarget(null)
      await load()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <section className="rb-page">
      <header className="rb-page-head rb-page-head-row">
        <div>
          <h2>Campanhas</h2>
          <p>Promoções e cupões da cadeia</p>
        </div>
        <button type="button" className="rb-btn-outline" onClick={load} disabled={loading}>
          {loading ? 'A carregar...' : 'Atualizar'}
        </button>
      </header>

      <article className="rb-table-card">
        <div className="rb-table-head">
          <h3>Promoções</h3>
          <button
            type="button"
            className="rb-primary"
            onClick={() => {
              setPromotionDraft(emptyPromotionDraft())
              setEditingPromotionId('')
              setErrorText('')
              setShowPromotionForm(true)
            }}
          >
            + Nova promoção
          </button>
        </div>

        {loading ? (
          <div className="rb-empty-state rb-empty-state-inline">
            <h3>A carregar promoções...</h3>
          </div>
        ) : null}

        {promotions.length === 0 && !loading ? (
          <div className="rb-empty-state rb-empty-state-inline">
            <h3>Sem promoções</h3>
            <p>Cria uma promoção para a cadeia quando quiseres destacar uma oferta.</p>
          </div>
        ) : null}

        {!loading && promotions.map((promotion) => {
          const isExpanded = expandedPromotionId === promotion.id
          return (
            <div key={promotion.id}>
              <div className="rb-detail-row">
                <span
                  role="button"
                  tabIndex={0}
                  onClick={() =>
                    setExpandedPromotionId((current) => (current === promotion.id ? '' : promotion.id))
                  }
                  onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                      event.preventDefault()
                      setExpandedPromotionId((current) => (current === promotion.id ? '' : promotion.id))
                    }
                  }}
                  style={{ cursor: 'pointer' }}
                >
                  <strong>{isExpanded ? '\u25BE' : '\u25B8'} {promotion.name}</strong>
                  <br />
                  <small>
                    {formatDiscount(promotion.type, promotion.discount)} - {targetLabel(promotion.target)}
                    {promotion.start_date ? ` - de ${dateInputValue(promotion.start_date)}` : ''}
                    {promotion.end_date ? ` até ${dateInputValue(promotion.end_date)}` : ''}
                  </small>
                </span>
                <div className="rb-card-actions">
                  <button
                    type="button"
                    className="rb-icon-mini"
                    onClick={() => startEditPromotion(promotion)}
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    className="rb-icon-mini danger"
                    onClick={() => setDeletePromotionTarget(promotion)}
                  >
                    Apagar
                  </button>
                </div>
              </div>
              {isExpanded ? (
                <div className="rb-detail-expanded">
                  {promotion.description ? <p>{promotion.description}</p> : null}
                  <p>
                    <strong>Tipo:</strong> {discountTypeLabel(promotion.type)}
                    {' \u00B7 '}
                    <strong>Alvo:</strong> {targetLabel(promotion.target)}
                    {' \u00B7 '}
                    <strong>Desconto:</strong> {formatDiscount(promotion.type, promotion.discount)}
                  </p>
                  {promotion.target === 'PRODUCT' || promotion.target === 'CATEGORY' ? (
                    <>
                      <strong>Itens abrangidos</strong>
                      {(promotion.promotionItems ?? []).length === 0 ? (
                        <p><small>Sem itens associados.</small></p>
                      ) : (
                        <ul>
                          {(promotion.promotionItems ?? []).map((item) => (
                            <li key={item.id}>{itemLabel(item)}</li>
                          ))}
                        </ul>
                      )}
                    </>
                  ) : null}
                </div>
              ) : null}
            </div>
          )
        })}
      </article>

      <article className="rb-table-card">
        <div className="rb-table-head">
          <h3>Cupões</h3>
          <button
            type="button"
            className="rb-primary"
            onClick={() => {
              setCouponDraft(emptyCouponDraft())
              setEditingCouponId('')
              setErrorText('')
              setShowCouponForm(true)
            }}
          >
            + Novo cupão
          </button>
        </div>

        {loading ? (
          <div className="rb-empty-state rb-empty-state-inline">
            <h3>A carregar cupões...</h3>
          </div>
        ) : null}

        {coupons.length === 0 && !loading ? (
          <div className="rb-empty-state rb-empty-state-inline">
            <h3>Sem cupões</h3>
            <p>Cria um cupão quando quiseres lançar um código promocional.</p>
          </div>
        ) : null}

        {!loading && coupons.map((coupon) => {
          const isExpanded = expandedCouponId === coupon.id
          return (
            <div key={coupon.id}>
              <div className="rb-detail-row">
                <span
                  role="button"
                  tabIndex={0}
                  onClick={() =>
                    setExpandedCouponId((current) => (current === coupon.id ? '' : coupon.id))
                  }
                  onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                      event.preventDefault()
                      setExpandedCouponId((current) => (current === coupon.id ? '' : coupon.id))
                    }
                  }}
                  style={{ cursor: 'pointer' }}
                >
                  <strong>{isExpanded ? '\u25BE' : '\u25B8'} {coupon.code}</strong>
                  <br />
                  <small>
                    {formatDiscount(coupon.type, coupon.discount)} - {targetLabel(coupon.target)}
                    {coupon.expiry_date ? ` até ${dateInputValue(coupon.expiry_date)}` : ''}
                  </small>
                </span>
                <div className="rb-card-actions">
                  <button
                    type="button"
                    className="rb-icon-mini"
                    onClick={() => startEditCoupon(coupon)}
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    className="rb-icon-mini danger"
                    onClick={() => setDeleteCouponTarget(coupon)}
                  >
                    Apagar
                  </button>
                </div>
              </div>
              {isExpanded ? (
                <div className="rb-detail-expanded">
                  {coupon.description ? <p>{coupon.description}</p> : null}
                  <p>
                    <strong>Tipo:</strong> {discountTypeLabel(coupon.type)}
                    {' \u00B7 '}
                    <strong>Alvo:</strong> {targetLabel(coupon.target)}
                    {' \u00B7 '}
                    <strong>Desconto:</strong> {formatDiscount(coupon.type, coupon.discount)}
                  </p>
                  {coupon.target === 'PRODUCT' || coupon.target === 'CATEGORY' ? (
                    <>
                      <strong>Itens abrangidos</strong>
                      {(coupon.promotionItems ?? []).length === 0 ? (
                        <p><small>Sem itens associados.</small></p>
                      ) : (
                        <ul>
                          {(coupon.promotionItems ?? []).map((item) => (
                            <li key={item.id}>{itemLabel(item)}</li>
                          ))}
                        </ul>
                      )}
                    </>
                  ) : null}
                </div>
              ) : null}
            </div>
          )
        })}
      </article>

      {infoText ? <p className="rb-success-note">{infoText}</p> : null}
      {errorText ? <p className="rb-chat-error">{errorText}</p> : null}

      <ConfirmDialog
        open={showPromotionForm}
        title={editingPromotionId ? 'Editar promoção' : 'Criar promoção'}
        description="Define o desconto e escolhe o alvo da promoção."
        confirmLabel={editingPromotionId ? 'Guardar alterações' : 'Criar promoção'}
        cancelLabel="Fechar"
        cardClassName="rb-dialog-card-wide"
        bodyClassName="rb-create-modal-body"
        loading={saving}
        onCancel={() => {
          if (!saving) {
            setShowPromotionForm(false)
            setEditingPromotionId('')
            setPromotionDraft(emptyPromotionDraft())
            setErrorText('')
          }
        }}
        onConfirm={handleSavePromotion}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <label>
            Nome
            <input
              value={promotionDraft.name}
              onChange={(event) =>
                setPromotionDraft((current) => ({ ...current, name: event.target.value }))
              }
              placeholder="Ex: Almoços de semana"
            />
          </label>
          <label>
            Descrição
            <input
              value={promotionDraft.description}
              onChange={(event) =>
                setPromotionDraft((current) => ({ ...current, description: event.target.value }))
              }
              placeholder="Ex: Desconto nos pedidos ao almoço"
            />
          </label>
          <div className="rb-login-grid">
            <label>
              Tipo
              <select
                value={promotionDraft.type}
                onChange={(event) =>
                  setPromotionDraft((current) => ({ ...current, type: event.target.value }))
                }
              >
                {DISCOUNT_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {discountTypeLabel(type)}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Alvo
              <select
                value={promotionDraft.target}
                onChange={(event) =>
                  setPromotionDraft((current) => ({
                    ...current,
                    target: event.target.value,
                    item_ids: [],
                  }))
                }
              >
                {DISCOUNT_TARGETS.map((target) => (
                  <option key={target} value={target}>
                    {targetLabel(target)}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <label>
            Valor do desconto ({promotionDraft.type === 'PERCENTAGE' ? '%' : 'EUR'})
            <input
              type="number"
              min="0.01"
              max={promotionDraft.type === 'PERCENTAGE' ? '100' : undefined}
              step="0.01"
              value={promotionDraft.discount}
              onChange={(event) =>
                setPromotionDraft((current) => ({ ...current, discount: event.target.value }))
              }
              placeholder="Ex: 10"
            />
          </label>
          {promotionDraft.target === 'PRODUCT' ? (
            <fieldset className="rb-checkbox-list">
              <legend>Produtos abrangidos</legend>
              {products.length === 0 ? (
                <p><small>Sem produtos na cadeia.</small></p>
              ) : (
                products.map((product) => (
                  <label key={product.id} className="rb-checkbox-item">
                    <input
                      type="checkbox"
                      checked={promotionDraft.item_ids.includes(product.id)}
                      onChange={() =>
                        setPromotionDraft((current) => ({
                          ...current,
                          item_ids: toggleId(current.item_ids, product.id),
                        }))
                      }
                    />
                    {product.name} ({product.category_name})
                  </label>
                ))
              )}
            </fieldset>
          ) : null}
          {promotionDraft.target === 'CATEGORY' ? (
            <fieldset className="rb-checkbox-list">
              <legend>Categorias abrangidas</legend>
              {categories.length === 0 ? (
                <p><small>Sem categorias na cadeia.</small></p>
              ) : (
                categories.map((category) => (
                  <label key={category.id} className="rb-checkbox-item">
                    <input
                      type="checkbox"
                      checked={promotionDraft.item_ids.includes(category.id)}
                      onChange={() =>
                        setPromotionDraft((current) => ({
                          ...current,
                          item_ids: toggleId(current.item_ids, category.id),
                        }))
                      }
                    />
                    {category.name}
                  </label>
                ))
              )}
            </fieldset>
          ) : null}
          <div className="rb-login-grid">
            <label>
              Início
              <input
                type="date"
                value={promotionDraft.start_date}
                onChange={(event) =>
                  setPromotionDraft((current) => ({ ...current, start_date: event.target.value }))
                }
              />
            </label>
            <label>
              Fim
              <input
                type="date"
                value={promotionDraft.end_date}
                min={promotionDraft.start_date || undefined}
                onChange={(event) =>
                  setPromotionDraft((current) => ({ ...current, end_date: event.target.value }))
                }
              />
            </label>
          </div>
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={showCouponForm}
        title={editingCouponId ? 'Editar cupão' : 'Criar cupão'}
        description="Define o código, o desconto e a validade do cupão."
        confirmLabel={editingCouponId ? 'Guardar alterações' : 'Criar cupão'}
        cancelLabel="Fechar"
        cardClassName="rb-dialog-card-wide"
        bodyClassName="rb-create-modal-body"
        loading={saving}
        onCancel={() => {
          if (!saving) {
            setShowCouponForm(false)
            setEditingCouponId('')
            setCouponDraft(emptyCouponDraft())
            setErrorText('')
          }
        }}
        onConfirm={handleSaveCoupon}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <label>
            Código
            <input
              value={couponDraft.code}
              onChange={(event) =>
                setCouponDraft((current) => ({ ...current, code: event.target.value }))
              }
              placeholder="Ex: LUNCH10"
            />
          </label>
          <label>
            Descrição
            <input
              value={couponDraft.description}
              onChange={(event) =>
                setCouponDraft((current) => ({ ...current, description: event.target.value }))
              }
              placeholder="Ex: Cupão para a hora de almoço"
            />
          </label>
          <div className="rb-login-grid">
            <label>
              Tipo
              <select
                value={couponDraft.type}
                onChange={(event) =>
                  setCouponDraft((current) => ({ ...current, type: event.target.value }))
                }
              >
                {DISCOUNT_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {discountTypeLabel(type)}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Alvo
              <select
                value={couponDraft.target}
                onChange={(event) =>
                  setCouponDraft((current) => ({
                    ...current,
                    target: event.target.value,
                    item_ids: [],
                  }))
                }
              >
                {DISCOUNT_TARGETS.map((target) => (
                  <option key={target} value={target}>
                    {targetLabel(target)}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <label>
            Valor do desconto ({couponDraft.type === 'PERCENTAGE' ? '%' : 'EUR'})
            <input
              type="number"
              min="0.01"
              max={couponDraft.type === 'PERCENTAGE' ? '100' : undefined}
              step="0.01"
              value={couponDraft.discount}
              onChange={(event) =>
                setCouponDraft((current) => ({ ...current, discount: event.target.value }))
              }
              placeholder="Ex: 10"
            />
          </label>
          {couponDraft.target === 'PRODUCT' ? (
            <fieldset className="rb-checkbox-list">
              <legend>Produtos abrangidos</legend>
              {products.length === 0 ? (
                <p><small>Sem produtos na cadeia.</small></p>
              ) : (
                products.map((product) => (
                  <label key={product.id} className="rb-checkbox-item">
                    <input
                      type="checkbox"
                      checked={couponDraft.item_ids.includes(product.id)}
                      onChange={() =>
                        setCouponDraft((current) => ({
                          ...current,
                          item_ids: toggleId(current.item_ids, product.id),
                        }))
                      }
                    />
                    {product.name} ({product.category_name})
                  </label>
                ))
              )}
            </fieldset>
          ) : null}
          {couponDraft.target === 'CATEGORY' ? (
            <fieldset className="rb-checkbox-list">
              <legend>Categorias abrangidas</legend>
              {categories.length === 0 ? (
                <p><small>Sem categorias na cadeia.</small></p>
              ) : (
                categories.map((category) => (
                  <label key={category.id} className="rb-checkbox-item">
                    <input
                      type="checkbox"
                      checked={couponDraft.item_ids.includes(category.id)}
                      onChange={() =>
                        setCouponDraft((current) => ({
                          ...current,
                          item_ids: toggleId(current.item_ids, category.id),
                        }))
                      }
                    />
                    {category.name}
                  </label>
                ))
              )}
            </fieldset>
          ) : null}
          <label>
            Validade
            <input
              type="date"
              min={todayInputValue()}
              value={couponDraft.expiry_date}
              onChange={(event) =>
                setCouponDraft((current) => ({ ...current, expiry_date: event.target.value }))
              }
            />
          </label>
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={Boolean(deletePromotionTarget)}
        title="Apagar promoção"
        description={`Apagar "${deletePromotionTarget?.name ?? ''}". Os clientes deixam de a ver.`}
        confirmLabel="Apagar"
        destructive
        loading={saving}
        onCancel={() => {
          if (!saving) setDeletePromotionTarget(null)
        }}
        onConfirm={handleConfirmDeletePromotion}
      />

      <ConfirmDialog
        open={Boolean(deleteCouponTarget)}
        title="Apagar cupão"
        description={`Apagar cupão "${deleteCouponTarget?.code ?? ''}".`}
        confirmLabel="Apagar"
        destructive
        loading={saving}
        onCancel={() => {
          if (!saving) setDeleteCouponTarget(null)
        }}
        onConfirm={handleConfirmDeleteCoupon}
      />
    </section>
  )
}
