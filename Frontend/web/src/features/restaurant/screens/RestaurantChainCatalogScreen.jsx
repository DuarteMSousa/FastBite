import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  createChainCategory,
  createChainProduct,
  deleteChainCategory,
  deleteChainProduct,
  fetchChainCatalog,
  fetchChainCategories,
  fetchProductOptionGroupsAdmin,
  updateChainCategory,
  updateChainProduct,
} from '../../../services/restaurantOpsService'
import { ConfirmDialog } from '../../../components/common/ConfirmDialog'

function categoryLabel(value) {
  return value?.trim() || 'Sem categoria'
}

function validatePrice(value, label = 'O preço') {
  const price = Number(value)
  if (value === '' || !Number.isFinite(price) || price < 0.01) {
    return `${label} tem de ser pelo menos 0.01 EUR.`
  }
  return ''
}

function validateOptionGroups(groups) {
  for (const [groupIndex, group] of groups.entries()) {
    const min = Number(group.min_options ?? 0)
    const max = Number(group.max_options ?? 1)
    if (min < 0) return `O mínimo do grupo ${groupIndex + 1} não pode ser negativo.`
    if (max < 1) return `O máximo do grupo ${groupIndex + 1} tem de ser pelo menos 1.`
    if (min > max) return `O mínimo do grupo ${groupIndex + 1} não pode ser maior que o máximo.`

    for (const [optionIndex, option] of (group.options ?? []).entries()) {
      const extraPrice = Number(option.extra_price ?? 0)
      if (!Number.isFinite(extraPrice) || extraPrice < 0) {
        return `O preço extra da opção ${optionIndex + 1} no grupo ${groupIndex + 1} não pode ser negativo.`
      }
    }
  }
  return ''
}

export function RestaurantChainCatalogScreen({ session }) {
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [searchText, setSearchText] = useState('')
  const [activeCategory, setActiveCategory] = useState('Todas')
  const [editingProductId, setEditingProductId] = useState('')
  const [editDraft, setEditDraft] = useState({ name: '', description: '', price: '' })
  const [editOptionGroups, setEditOptionGroups] = useState([])
  const [showOptionsEditor, setShowOptionsEditor] = useState(false)
  const [showCreateForm, setShowCreateForm] = useState(false)
  const [newProduct, setNewProduct] = useState({
    category: '',
    name: '',
    description: '',
    price: '',
  })
  const [newProductOptionGroups, setNewProductOptionGroups] = useState([])
  const [errorText, setErrorText] = useState('')
  const [infoText, setInfoText] = useState('')
  const [deleteTarget, setDeleteTarget] = useState(null)
  const [showCategoriesModal, setShowCategoriesModal] = useState(false)
  const [newCategoryName, setNewCategoryName] = useState('')
  const [categoryDraft, setCategoryDraft] = useState({ id: '', name: '' })
  const [deleteCategoryTarget, setDeleteCategoryTarget] = useState(null)

  const isChainManager = Boolean(session?.isChainManager)

  const categoryOptions = useMemo(() => {
    return Array.from(new Set(categories.map((entry) => entry?.name?.trim()).filter(Boolean)))
  }, [categories])

  const loadCatalog = useCallback(async () => {
    if (!session?.chainId) {
      setErrorText('Sem chain_id na sessão.')
      setLoading(false)
      return
    }

    try {
      setLoading(true)
      setProducts([])
      const data = await fetchChainCatalog({ session })
      setCategories(data.categories)
      setProducts(data.products)
      setErrorText('')
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoading(false)
    }
  }, [session])

  useEffect(() => {
    queueMicrotask(() => {
      loadCatalog()
    })
  }, [loadCatalog])

  const visibleCategories = useMemo(() => {
    const names = new Set(['Todas'])
    products.forEach((product) => names.add(categoryLabel(product.category_name)))
    return Array.from(names)
  }, [products])

  const visibleProducts = useMemo(() => {
    return products.filter((product) => {
      const category = categoryLabel(product.category_name)
      const matchesCategory = activeCategory === 'Todas' || category === activeCategory
      const query = searchText.trim().toLowerCase()
      const matchesSearch =
        query === '' ||
        (product.name ?? '').toLowerCase().includes(query) ||
        (product.description ?? '').toLowerCase().includes(query)
      return matchesCategory && matchesSearch
    })
  }, [activeCategory, products, searchText])

  const selectedEditingProduct = useMemo(
    () => products.find((product) => product.id === editingProductId) ?? null,
    [editingProductId, products],
  )

  async function startEdit(product) {
    setErrorText('')
    setEditingProductId(product.id)
    setEditDraft({
      name: product.name ?? '',
      description: product.description ?? '',
      price: String(Number(product.price ?? 0).toFixed(2)),
    })
    setShowOptionsEditor(false)
    try {
      const groups = await fetchProductOptionGroupsAdmin({ session, productId: product.id })
      setEditOptionGroups(groups)
    } catch {
      setEditOptionGroups([])
    }
  }

  function cancelEdit() {
    setEditingProductId('')
    setEditDraft({ name: '', description: '', price: '' })
    setEditOptionGroups([])
    setShowOptionsEditor(false)
  }

  async function saveEdit(product) {
    if (!editDraft.name.trim()) {
      setErrorText('Nome obrigatório.')
      return
    }
    const priceError = validatePrice(editDraft.price)
    if (priceError) {
      setErrorText(priceError)
      return
    }
    const optionsError = showOptionsEditor ? validateOptionGroups(editOptionGroups) : ''
    if (optionsError) {
      setErrorText(optionsError)
      return
    }

    try {
      setSaving(true)
      setErrorText('')
      await updateChainProduct({
        session,
        productId: product.id,
        input: {
          name: editDraft.name.trim(),
          description: editDraft.description.trim(),
          price: Number(editDraft.price),
          option_groups: showOptionsEditor ? editOptionGroups : undefined,
        },
      })

      setInfoText('Produto da cadeia atualizado.')
      cancelEdit()
      await loadCatalog()
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
      await deleteChainProduct({ session, productId: deleteTarget.id })
      setInfoText('Produto removido da cadeia.')
      setDeleteTarget(null)
      await loadCatalog()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function handleCreate() {
    if (!newProduct.name.trim() || !newProduct.category.trim() || !newProduct.price.trim()) {
      setErrorText('Preenche categoria, nome e preço para criar o produto.')
      return
    }
    const priceError = validatePrice(newProduct.price)
    if (priceError) {
      setErrorText(priceError)
      return
    }
    const optionsError = validateOptionGroups(newProductOptionGroups)
    if (optionsError) {
      setErrorText(optionsError)
      return
    }

    try {
      setSaving(true)
      setErrorText('')
      await createChainProduct({
        session,
        input: {
          category: newProduct.category.trim(),
          name: newProduct.name.trim(),
          description: newProduct.description.trim() || null,
          price: Number(newProduct.price),
          option_groups: newProductOptionGroups,
        },
      })

      setInfoText('Produto criado na cadeia.')
      setShowCreateForm(false)
      setNewProduct({ category: '', name: '', description: '', price: '' })
      setNewProductOptionGroups([])
      await loadCatalog()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  const loadCategoriesForModal = useCallback(async () => {
    try {
      const list = await fetchChainCategories({ session })
      setCategories(list)
    } catch (error) {
      setErrorText(error.message)
    }
  }, [session])

  function openCategoriesModal() {
    setShowCategoriesModal(true)
    loadCategoriesForModal()
  }

  useEffect(() => {
    if (!showCreateForm) return undefined
    queueMicrotask(() => {
      loadCategoriesForModal()
    })
    return undefined
  }, [loadCategoriesForModal, showCreateForm])

  async function handleCreateCategory() {
    const name = newCategoryName.trim()
    if (!name) {
      setErrorText('Nome de categoria obrigatório.')
      return
    }
    try {
      setSaving(true)
      await createChainCategory({ session, name })
      setNewCategoryName('')
      await loadCategoriesForModal()
      await loadCatalog()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function handleSaveCategoryDraft() {
    if (!categoryDraft.id || !categoryDraft.name.trim()) return
    try {
      setSaving(true)
      await updateChainCategory({
        session,
        categoryId: categoryDraft.id,
        name: categoryDraft.name,
      })
      setCategoryDraft({ id: '', name: '' })
      await loadCategoriesForModal()
      await loadCatalog()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  async function handleDeleteCategoryConfirmed() {
    if (!deleteCategoryTarget) return
    try {
      setSaving(true)
      await deleteChainCategory({ session, categoryId: deleteCategoryTarget.id })
      setDeleteCategoryTarget(null)
      await loadCategoriesForModal()
      await loadCatalog()
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setSaving(false)
    }
  }

  function addOptionGroup(setter) {
    setter((current) => [
      ...current,
      { name: '', min_options: 0, max_options: 1, options: [] },
    ])
  }

  function updateOptionGroup(setter, index, patch) {
    setter((current) => current.map((group, idx) => (idx === index ? { ...group, ...patch } : group)))
  }

  function removeOptionGroup(setter, index) {
    setter((current) => current.filter((_, idx) => idx !== index))
  }

  function addOption(setter, groupIndex) {
    setter((current) =>
      current.map((group, idx) =>
        idx === groupIndex
          ? { ...group, options: [...group.options, { name: '', extra_price: 0, default_option: false }] }
          : group,
      ),
    )
  }

  function updateOption(setter, groupIndex, optionIndex, patch) {
    setter((current) =>
      current.map((group, gIdx) =>
        gIdx === groupIndex
          ? {
              ...group,
              options: group.options.map((option, oIdx) =>
                oIdx === optionIndex ? { ...option, ...patch } : option,
              ),
            }
          : group,
      ),
    )
  }

  function removeOption(setter, groupIndex, optionIndex) {
    setter((current) =>
      current.map((group, gIdx) =>
        gIdx === groupIndex
          ? { ...group, options: group.options.filter((_, oIdx) => oIdx !== optionIndex) }
          : group,
      ),
    )
  }

  function renderOptionGroupsEditor({ groups, setter, keyPrefix, emptyText }) {
    return (
      <div className="rb-option-editor">
        <div className="rb-option-editor-head">
          <strong>Grupos de opções</strong>
          <button
            type="button"
            className="rb-btn-outline"
            onClick={() => addOptionGroup(setter)}
          >
            + Adicionar grupo
          </button>
        </div>
        {groups.length === 0 ? <small>{emptyText}</small> : null}

        {groups.map((group, groupIndex) => (
          <div className="rb-option-group" key={`${keyPrefix}-group-${groupIndex}`}>
            <div className="rb-option-group-head">
              <label className="rb-option-field">
                Nome do grupo
                <input
                  value={group.name}
                  onChange={(event) =>
                    updateOptionGroup(setter, groupIndex, { name: event.target.value })
                  }
                />
              </label>
              <button
                type="button"
                className="rb-icon-mini danger"
                onClick={() => removeOptionGroup(setter, groupIndex)}
              >
                Remover grupo
              </button>
            </div>
            <div className="rb-option-group-rules">
              <label>
                Mínimo
                <input
                  type="number"
                  min="0"
                  step="1"
                  value={group.min_options}
                  onChange={(event) =>
                    updateOptionGroup(setter, groupIndex, {
                      min_options: Number(event.target.value),
                    })
                  }
                />
              </label>
              <label>
                Máximo
                <input
                  type="number"
                  min="1"
                  step="1"
                  value={group.max_options}
                  onChange={(event) =>
                    updateOptionGroup(setter, groupIndex, {
                      max_options: Number(event.target.value),
                    })
                  }
                />
              </label>
            </div>

            {group.options.map((option, optionIndex) => (
              <div className="rb-option-row" key={`${keyPrefix}-option-${groupIndex}-${optionIndex}`}>
                <label className="rb-option-field">
                  Nome da opção
                  <input
                    value={option.name}
                    onChange={(event) =>
                      updateOption(setter, groupIndex, optionIndex, {
                        name: event.target.value,
                      })
                    }
                  />
                </label>
                <label className="rb-option-field">
                  Preço extra (EUR)
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={option.extra_price}
                    onChange={(event) =>
                      updateOption(setter, groupIndex, optionIndex, {
                        extra_price: Number(event.target.value),
                      })
                    }
                  />
                </label>
                <label className="rb-option-default">
                  <input
                    type="checkbox"
                    checked={option.default_option}
                    onChange={(event) =>
                      updateOption(setter, groupIndex, optionIndex, {
                        default_option: event.target.checked,
                      })
                    }
                  />
                  Predefinida
                </label>
                <button
                  type="button"
                  className="rb-icon-mini danger"
                  onClick={() => removeOption(setter, groupIndex, optionIndex)}
                  aria-label="Remover opção"
                >
                  x
                </button>
              </div>
            ))}

            <button
              type="button"
              className="rb-btn-outline"
              onClick={() => addOption(setter, groupIndex)}
            >
              + Adicionar opção
            </button>
          </div>
        ))}
      </div>
    )
  }

  if (!isChainManager) {
    return (
      <section className="rb-page">
        <header className="rb-page-head">
          <h2>Catálogo da cadeia</h2>
          <p>Apenas gestores de cadeia podem editar o catálogo.</p>
        </header>
      </section>
    )
  }

  return (
    <section className="rb-page">
      <header className="rb-page-head rb-page-head-row">
        <div>
          <h2>Catálogo da cadeia</h2>
          <p>Produtos base da cadeia: nome, descrição, preço base e grupos de opções</p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button type="button" className="rb-btn-outline" onClick={openCategoriesModal}>
            Gerir categorias
          </button>
          <button
            type="button"
            className="rb-primary"
            onClick={() => {
              setErrorText('')
              setShowCreateForm((state) => !state)
            }}
          >
            {showCreateForm ? 'Fechar criação' : '+ Adicionar produto'}
          </button>
        </div>
      </header>

      <article className="rb-search-wrap">
        <input
          className="rb-search"
          placeholder="Procurar produtos..."
          value={searchText}
          onChange={(event) => setSearchText(event.target.value)}
        />
        <div className="rb-filter-row">
          {visibleCategories.map((category) => (
            <button
              key={category}
              type="button"
              className={`rb-filter ${activeCategory === category ? 'active' : ''}`}
              onClick={() => setActiveCategory(category)}
            >
              {category}
            </button>
          ))}
          <button type="button" className="rb-filter" onClick={loadCatalog} disabled={loading}>
            Atualizar
          </button>
        </div>
      </article>

      <div className="rb-menu-grid">
        {loading ? (
          <div className="rb-empty-state rb-empty-state-inline rb-grid-empty">
            <h3>A carregar catálogo...</h3>
          </div>
        ) : null}
        {!loading && visibleProducts.length === 0 ? (
          <div className="rb-empty-state rb-empty-state-inline rb-grid-empty">
            <h3>Sem produtos no catálogo</h3>
            <p>Adiciona o primeiro produto base da cadeia.</p>
          </div>
        ) : null}

        {!loading && visibleProducts.map((product) => {
          return (
            <article className="rb-menu-card" key={product.id}>
              <div className="rb-menu-banner">{categoryLabel(product.category_name).toLowerCase()}</div>
              <div className="rb-menu-content">
                <div className="rb-menu-top">
                  <h4>{product.name ?? 'Produto'}</h4>
                  <strong>{Number(product.price ?? 0).toFixed(2)} EUR</strong>
                </div>
                <span className="rb-menu-tag">{categoryLabel(product.category_name)}</span>
                <p>{product.description || 'Sem descrição'}</p>
                <div className="rb-menu-bottom">
                  <span className="rb-chip">Base da cadeia</span>
                  <div className="rb-card-actions">
                    <button type="button" className="rb-icon-mini" onClick={() => startEdit(product)}>
                      Editar
                    </button>
                    <button
                      type="button"
                      className="rb-icon-mini danger"
                      onClick={() => requestDelete(product)}
                    >
                      Apagar
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
        title="Editar produto da cadeia"
        description="Atualiza o produto base e, se precisares, os seus grupos de opções."
        confirmLabel="Guardar alterações"
        cancelLabel="Fechar"
        cardClassName="rb-dialog-card-wide"
        bodyClassName="rb-create-modal-body"
        loading={saving}
        onCancel={() => {
          if (!saving) cancelEdit()
        }}
        onConfirm={() => {
          if (selectedEditingProduct) saveEdit(selectedEditingProduct)
        }}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <label>
            Nome
            <input
              type="text"
              value={editDraft.name}
              onChange={(event) =>
                setEditDraft((state) => ({ ...state, name: event.target.value }))
              }
            />
          </label>
          <label>
            Descrição
            <input
              type="text"
              value={editDraft.description}
              onChange={(event) =>
                setEditDraft((state) => ({ ...state, description: event.target.value }))
              }
            />
          </label>
          <label>
            Preço base (EUR)
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

          <button
            type="button"
            className="rb-btn-outline"
            onClick={() => setShowOptionsEditor((state) => !state)}
          >
            {showOptionsEditor ? 'Esconder grupos de opções' : 'Editar grupos de opções'}
          </button>

          {showOptionsEditor
            ? renderOptionGroupsEditor({
                groups: editOptionGroups,
                setter: setEditOptionGroups,
                keyPrefix: 'edit',
                emptyText: 'Sem grupos.',
              })
            : null}
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={showCreateForm}
        title="Criar produto da cadeia"
        description="Cria um produto base. Cada restaurante pode depois ativá-lo no seu menu."
        confirmLabel="Criar produto"
        cancelLabel="Fechar"
        cardClassName="rb-dialog-card-wide"
        bodyClassName="rb-create-modal-body"
        loading={saving}
        onCancel={() => {
          if (!saving) setShowCreateForm(false)
        }}
        onConfirm={handleCreate}
      >
        <div className="rb-login-form rb-create-product-modal-form">
          <label>
            Categoria
            <select
              value={newProduct.category}
              onChange={(event) =>
                setNewProduct((current) => ({ ...current, category: event.target.value }))
              }
            >
              <option value="">Seleciona uma categoria</option>
              {categoryOptions.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </select>
          </label>
          <label>
            Nome
            <input
              value={newProduct.name}
              onChange={(event) =>
                setNewProduct((current) => ({ ...current, name: event.target.value }))
              }
              placeholder="Ex: Pizza margherita"
            />
          </label>
          <label>
            Preço base (EUR)
            <input
              type="number"
              min="0.01"
              step="0.01"
              value={newProduct.price}
              onChange={(event) =>
                setNewProduct((current) => ({ ...current, price: event.target.value }))
              }
              placeholder="Ex: 9.50"
            />
          </label>
          <label>
            Descrição
            <input
              value={newProduct.description}
              onChange={(event) =>
                setNewProduct((current) => ({ ...current, description: event.target.value }))
              }
              placeholder="Ex: Tomate, mozzarella e manjericão"
            />
          </label>
          {renderOptionGroupsEditor({
            groups: newProductOptionGroups,
            setter: setNewProductOptionGroups,
            keyPrefix: 'create',
            emptyText: 'Sem grupos. Útil para escolhas como "tamanho" ou "molho".',
          })}
          {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        title="Apagar produto da cadeia"
        description={`Vais apagar "${deleteTarget?.name ?? 'produto'}" do catálogo da cadeia. Isto remove-o de todos os restaurantes.`}
        confirmLabel="Apagar"
        destructive
        loading={saving}
        onCancel={() => {
          if (!saving) setDeleteTarget(null)
        }}
        onConfirm={confirmDelete}
      />

      <ConfirmDialog
        open={showCategoriesModal}
        title="Gerir categorias"
        description="Adicionar, renomear ou apagar categorias da cadeia."
        confirmLabel="Fechar"
        loading={saving}
        onCancel={() => {
          if (!saving) {
            setShowCategoriesModal(false)
            setCategoryDraft({ id: '', name: '' })
          }
        }}
        onConfirm={() => {
          if (!saving) {
            setShowCategoriesModal(false)
            setCategoryDraft({ id: '', name: '' })
          }
        }}
      >
        <div className="rb-categories-list">
          {categories.length === 0 ? (
            <div className="rb-empty-state rb-empty-state-inline">
              <h3>Sem categorias</h3>
              <p>Cria uma categoria para organizar o catálogo.</p>
            </div>
          ) : null}
          {categories.map((category) => (
            <div key={category.id} className="rb-category-row">
              {categoryDraft.id === category.id ? (
                <>
                  <input
                    value={categoryDraft.name}
                    onChange={(event) =>
                      setCategoryDraft((current) => ({ ...current, name: event.target.value }))
                    }
                  />
                  <div className="rb-card-actions">
                    <button
                      type="button"
                      className="rb-icon-mini"
                      onClick={handleSaveCategoryDraft}
                      disabled={saving}
                    >
                      Guardar
                    </button>
                    <button
                      type="button"
                      className="rb-icon-mini"
                      onClick={() => setCategoryDraft({ id: '', name: '' })}
                    >
                      Cancelar
                    </button>
                  </div>
                </>
              ) : (
                <>
                  <strong>{category.name}</strong>
                  <div className="rb-card-actions">
                    <button
                      type="button"
                      className="rb-icon-mini"
                      onClick={() => setCategoryDraft({ id: category.id, name: category.name })}
                    >
                      Renomear
                    </button>
                    <button
                      type="button"
                      className="rb-icon-mini danger"
                      onClick={() => setDeleteCategoryTarget(category)}
                    >
                      Apagar
                    </button>
                  </div>
                </>
              )}
            </div>
          ))}
        </div>

        <div className="rb-category-form">
          <input
            placeholder="Nova categoria"
            value={newCategoryName}
            onChange={(event) => setNewCategoryName(event.target.value)}
          />
          <button type="button" className="rb-btn-accept" onClick={handleCreateCategory} disabled={saving}>
            Criar
          </button>
        </div>
      </ConfirmDialog>

      <ConfirmDialog
        open={Boolean(deleteCategoryTarget)}
        title="Apagar categoria"
        description={`Apagar "${deleteCategoryTarget?.name ?? ''}" da cadeia.`}
        confirmLabel="Apagar"
        destructive
        loading={saving}
        onCancel={() => {
          if (!saving) setDeleteCategoryTarget(null)
        }}
        onConfirm={handleDeleteCategoryConfirmed}
      />
    </section>
  )
}
