import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  addChainProductToRestaurantMenu,
  deleteRestaurantMenuProduct,
  fetchChainCatalog,
  fetchRestaurantMenuProducts,
  updateRestaurantMenuProduct,
} from '../../../services/restaurantOpsService'
import { ConfirmDialog } from '../../../components/common/ConfirmDialog'

function categoryLabel(value) {
  return value?.trim() || 'Sem categoria'
}

function availabilityLabel(isAvailable) {
  return isAvailable ? 'Disponível' : 'Indisponível'
}

function validatePrice(value, label = 'O preço') {
  const price = Number(value)
  if (value === '' || !Number.isFinite(price) || price < 0.01) {
    return `${label} tem de ser pelo menos 0.01 EUR.`
  }
  return ''
}

export function RestaurantMenuCatalogScreen({ session }) {
  const [products, setProducts] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [searchText, setSearchText] = useState('')
  const [activeCategory, setActiveCategory] = useState('Todas')
  const [editingProductId, setEditingProductId] = useState('')
  const [editDraft, setEditDraft] = useState({ price: '', prep: '', isAvailable: true })
  const [errorText, setErrorText] = useState('')
  const [infoText, setInfoText] = useState('')
  const [deleteTarget, setDeleteTarget] = useState(null)
  const [showAddFromCatalog, setShowAddFromCatalog] = useState(false)
  const [chainCatalogProducts, setChainCatalogProducts] = useState([])
  const [catalogSearch, setCatalogSearch] = useState('')
  const [pickerDraft, setPickerDraft] = useState({ productId: '', localPrice: '', prep: '' })

  const loadProducts = useCallback(async () => {
    try {
      setLoading(true)
      setProducts([])
      const data = await fetchRestaurantMenuProducts(session)
      setProducts(data)
      setErrorText('')
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoading(false)
    }
  }, [session])

  useEffect(() => {
    queueMicrotask(() => {
      loadProducts()
    })
  }, [loadProducts])

  const categories = useMemo(() => {
    const names = new Set(['Todas'])
    products.forEach((product) => names.add(categoryLabel(product.category)))
    return Array.from(names)
  }, [products])

  const visibleProducts = useMemo(() => {
    return products.filter((product) => {
      const category = categoryLabel(product.category)
      const matchesCategory = activeCategory === 'Todas' || category === activeCategory
      const query = searchText.trim().toLowerCase()
      const matchesSearch =
        query === '' ||
        (product.name ?? '').toLowerCase().includes(query) ||
        (product.description ?? '').toLowerCase().includes(query)

      return matchesCategory && matchesSearch
    })
  }, [activeCategory, products, searchText])

  const existingChainProductIds = useMemo(() => {
    return new Set(products.map((product) => product.product_id))
  }, [products])

  const selectedEditingProduct = useMemo(
    () => products.find((product) => product.restaurant_product_id === editingProductId) ?? null,
    [editingProductId, products],
  )

  const catalogCandidates = useMemo(() => {
    const query = catalogSearch.trim().toLowerCase()
    return chainCatalogProducts
      .filter((product) => !existingChainProductIds.has(product.id))
      .filter((product) => {
        if (!query) return true
        return (
          (product.name ?? '').toLowerCase().includes(query) ||
          (product.description ?? '').toLowerCase().includes(query) ||
          (product.category_name ?? '').toLowerCase().includes(query)
        )
      })
  }, [catalogSearch, chainCatalogProducts, existingChainProductIds])

  function startEdit(product) {
    setErrorText('')
    setEditingProductId(product.restaurant_product_id)
    setEditDraft({
      price: String(Number(product.price ?? 0).toFixed(2)),
      prep: product.estimated_preparation_time_min ? String(product.estimated_preparation_time_min) : '',
      isAvailable: Boolean(product.is_available),
    })
  }

  function cancelEdit() {
    setEditingProductId('')
    setEditDraft({ price: '', prep: '', isAvailable: true })
  }

  async function saveEdit(product) {
    const priceError = validatePrice(editDraft.price)
    if (priceError) {
      setErrorText(priceError)
      return
    }
    if (editDraft.prep.trim() !== '' && Number(editDraft.prep) < 1) {
      setErrorText('O tempo de preparação tem de ser pelo menos 1 minuto.')
      return
    }

    try {
      setSaving(true)
      setErrorText('')
      await updateRestaurantMenuProduct({
        session,
        input: {
          restaurant_product_id: product.restaurant_product_id,
          price: Number(editDraft.price),
          estimated_preparation_time_min:
            editDraft.prep.trim() === '' ? null : Number(editDraft.prep),
          is_available: Boolean(editDraft.isAvailable),
        },
      })

      setInfoText('Item do menu atualizado.')
      cancelEdit()
      await loadProducts()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function toggleAvailability(product) {
    try {
      setSaving(true)
      await updateRestaurantMenuProduct({
        session,
        input: {
          restaurant_product_id: product.restaurant_product_id,
          is_available: !product.is_available,
        },
      })

      setInfoText('Disponibilidade atualizada.')
      await loadProducts()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  function requestDelete(product) {
    setDeleteTarget(product)
  }

  async function confirmDelete() {
    if (!deleteTarget) return
    try {
      setSaving(true)
      const result = await deleteRestaurantMenuProduct({
        session,
        restaurantProductId: deleteTarget.restaurant_product_id,
      })

      setInfoText(result.message ?? 'Produto removido do menu.')
      setDeleteTarget(null)
      await loadProducts()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function openAddFromCatalog() {
    setErrorText('')
    setShowAddFromCatalog(true)
    setPickerDraft({ productId: '', localPrice: '', prep: '' })
    setCatalogSearch('')
    try {
      const catalog = await fetchChainCatalog({ session })
      setChainCatalogProducts(catalog.products)
    } catch (error) {
      setErrorText(error.message)
    }
  }

  async function handleAddFromCatalog() {
    if (!pickerDraft.productId) {
      setErrorText('Escolhe um produto do catálogo.')
      return
    }
    if (pickerDraft.localPrice !== '') {
      const priceError = validatePrice(pickerDraft.localPrice)
      if (priceError) {
        setErrorText(priceError)
        return
      }
    }
    if (pickerDraft.prep.trim() !== '' && Number(pickerDraft.prep) < 1) {
      setErrorText('O tempo de preparação tem de ser pelo menos 1 minuto.')
      return
    }

    try {
      setSaving(true)
      setErrorText('')
      await addChainProductToRestaurantMenu({
        session,
        productId: pickerDraft.productId,
        localPrice: pickerDraft.localPrice,
        estimatedPreparationTimeMin: pickerDraft.prep,
        isAvailable: true,
      })

      setInfoText('Produto adicionado ao menu do restaurante.')
      setShowAddFromCatalog(false)
      setPickerDraft({ productId: '', localPrice: '', prep: '' })
      await loadProducts()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  const selectedCatalogProduct = useMemo(
    () => chainCatalogProducts.find((product) => product.id === pickerDraft.productId) ?? null,
    [chainCatalogProducts, pickerDraft.productId],
  )

  return (
    <section className="rb-page">
      <header className="rb-page-head rb-page-head-row">
        <div>
          <h2>Menu do restaurante</h2>
          <p>Escolhe produtos da cadeia e ajusta preço, tempo de preparação e disponibilidade</p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button type="button" className="rb-primary" onClick={openAddFromCatalog}>
            + Adicionar do catálogo
          </button>
        </div>
      </header>

      <article className="rb-search-wrap">
        <input
          className="rb-search"
          placeholder="Procurar no menu..."
          value={searchText}
          onChange={(event) => setSearchText(event.target.value)}
        />
        <div className="rb-filter-row">
          {categories.map((category) => (
            <button
              key={category}
              type="button"
              className={`rb-filter ${activeCategory === category ? 'active' : ''}`}
              onClick={() => setActiveCategory(category)}
            >
              {category}
            </button>
          ))}
          <button type="button" className="rb-filter" onClick={loadProducts} disabled={loading}>
            Atualizar
          </button>
        </div>
      </article>

      <div className="rb-menu-grid">
        {loading ? (
          <div className="rb-empty-state rb-empty-state-inline rb-grid-empty">
            <h3>A carregar menu...</h3>
          </div>
        ) : null}
        {!loading && visibleProducts.length === 0 ? (
          <div className="rb-empty-state rb-empty-state-inline rb-grid-empty">
            <h3>Sem produtos no menu</h3>
            <p>Usa &quot;Adicionar do catálogo&quot; para escolher produtos da cadeia.</p>
          </div>
        ) : null}

        {!loading && visibleProducts.map((product) => {
          return (
            <article className="rb-menu-card" key={product.restaurant_product_id}>
              <div className="rb-menu-banner">{categoryLabel(product.category).toLowerCase()}</div>
              <div className="rb-menu-content">
                <div className="rb-menu-top">
                  <h4>{product.name ?? 'Produto'}</h4>
                  <strong>{Number(product.price ?? 0).toFixed(2)} EUR</strong>
                </div>
                <span className="rb-menu-tag">{categoryLabel(product.category)}</span>
                <p>{product.description || 'Sem descrição'}</p>
                <p>Preparação: {product.estimated_preparation_time_min ?? '-'} min</p>
                <div className="rb-menu-bottom">
                  <span className={`rb-chip ${product.is_available ? 'done' : 'off'}`}>
                    {availabilityLabel(product.is_available)}
                  </span>
                  <div className="rb-card-actions">
                    <button type="button" className="rb-icon-mini" onClick={() => toggleAvailability(product)}>
                      {product.is_available ? 'Desativar' : 'Ativar'}
                    </button>
                    <button type="button" className="rb-icon-mini" onClick={() => startEdit(product)}>
                      Editar
                    </button>
                    <button
                      type="button"
                      className="rb-icon-mini danger"
                      onClick={() => requestDelete(product)}
                    >
                      Remover
                    </button>
                  </div>
                </div>

              </div>
            </article>
          )
        })}
      </div>

      {infoText ? <p className="rb-prep-note">{infoText}</p> : null}
      {errorText ? <p className="rb-chat-error">{errorText}</p> : null}

      <ConfirmDialog
        open={Boolean(selectedEditingProduct)}
        title="Editar item do menu"
        description="Ajusta apenas os dados locais deste restaurante."
        confirmLabel="Guardar alterações"
        cancelLabel="Fechar"
        loading={saving}
        onCancel={() => {
          if (!saving) cancelEdit()
        }}
        onConfirm={() => {
          if (selectedEditingProduct) saveEdit(selectedEditingProduct)
        }}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <small>Nome, descrição e grupos de opções vêm do catálogo da cadeia.</small>
          <label>
            Preço local (EUR)
            <input
              type="number"
              min="0.01"
              step="0.01"
              value={editDraft.price}
              onChange={(event) =>
                setEditDraft((state) => ({ ...state, price: event.target.value }))
              }
            />
          </label>
          <label>
            Preparação (min)
            <input
              type="number"
              min="1"
              step="1"
              value={editDraft.prep}
              onChange={(event) => setEditDraft((state) => ({ ...state, prep: event.target.value }))}
              placeholder="Opcional"
            />
          </label>
          <label>
            <input
              type="checkbox"
              checked={editDraft.isAvailable}
              onChange={(event) =>
                setEditDraft((state) => ({ ...state, isAvailable: event.target.checked }))
              }
            />
            {' '}Disponível
          </label>
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={showAddFromCatalog}
        title="Adicionar do catálogo"
        description="Escolhe um produto da cadeia para acrescentar a este restaurante."
        confirmLabel="Adicionar"
        cancelLabel="Fechar"
        cardClassName="rb-dialog-card-wide"
        bodyClassName="rb-create-modal-body"
        loading={saving}
        onCancel={() => {
          if (!saving) setShowAddFromCatalog(false)
        }}
        onConfirm={handleAddFromCatalog}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <input
            className="rb-search"
            placeholder="Procurar no catálogo..."
            value={catalogSearch}
            onChange={(event) => setCatalogSearch(event.target.value)}
          />

          <div className="rb-categories-list">
            {catalogCandidates.length === 0 ? (
              <small>
                {chainCatalogProducts.length === 0
                  ? 'Catálogo vazio.'
                  : 'Sem produtos da cadeia que ainda não estejam no menu.'}
              </small>
            ) : null}
            {catalogCandidates.map((product) => (
              <label key={product.id} className="rb-category-row">
                <input
                  type="radio"
                  name="catalog-pick"
                  checked={pickerDraft.productId === product.id}
                  onChange={() =>
                    setPickerDraft((current) => ({
                      ...current,
                      productId: product.id,
                      localPrice: current.localPrice === '' ? String(Number(product.price ?? 0).toFixed(2)) : current.localPrice,
                    }))
                  }
                />
                <strong>{product.name}</strong>
                <span>{categoryLabel(product.category_name)}</span>
                <span>{Number(product.price ?? 0).toFixed(2)} EUR</span>
              </label>
            ))}
          </div>

          {selectedCatalogProduct ? (
            <>
              <label>
                Preço local (EUR)
                <input
                  type="number"
                  min="0.01"
                  step="0.01"
                  value={pickerDraft.localPrice}
                  onChange={(event) =>
                    setPickerDraft((current) => ({ ...current, localPrice: event.target.value }))
                  }
                  placeholder={`Base: ${Number(selectedCatalogProduct.price ?? 0).toFixed(2)}`}
                />
              </label>
              <label>
                Tempo de preparação (min)
                <input
                  type="number"
                  min="1"
                  step="1"
                  value={pickerDraft.prep}
                  onChange={(event) =>
                    setPickerDraft((current) => ({ ...current, prep: event.target.value }))
                  }
                  placeholder="Opcional"
                />
              </label>
            </>
          ) : null}
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        title="Remover do menu"
        description={`Vais remover "${deleteTarget?.name ?? 'produto'}" deste restaurante. Os clientes deixam de o ver.`}
        confirmLabel="Remover"
        destructive
        loading={saving}
        onCancel={() => {
          if (!saving) setDeleteTarget(null)
        }}
        onConfirm={confirmDelete}
      />
    </section>
  )
}
