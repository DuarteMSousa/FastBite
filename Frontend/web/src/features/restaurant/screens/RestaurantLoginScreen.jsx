import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  bootstrapRestaurantSession,
  completeRestaurantOnboarding,
  registerRestaurantUser,
  searchRestaurantChains,
} from '../../../services/restaurantOpsService'
import { RestaurantAddressMapPicker } from '../../../components/common/RestaurantAddressMapPicker'

const DEFAULT_TOKEN = import.meta.env.VITE_AUTH_BEARER_TOKEN ?? ''

const INITIAL_REGISTER_FORM = {
  name: '',
  email: '',
  password: '',
}

const INITIAL_RESTAURANT_FORM = {
  name: '',
  opening_hours: '09:00',
  closing_hours: '23:00',
  delivery_radius: 5,
  street: '',
  city: '',
  postal_code: '',
  country: 'Portugal',
  latitude: 41.1496,
  longitude: -8.6109,
}

const CHAIN_PAGE_SIZE = 20

function PasswordField({ value, onChange, visible, onToggle, placeholder = 'Palavra-passe' }) {
  return (
    <div className="rb-password-field">
      <input
        value={value}
        onChange={onChange}
        type={visible ? 'text' : 'password'}
        placeholder={placeholder}
        required
      />
      <button
        type="button"
        className="rb-password-toggle"
        onClick={onToggle}
        aria-label={visible ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'}
        title={visible ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'}
      >
        {visible ? (
          <svg aria-hidden="true" viewBox="0 0 24 24">
            <path d="m3 3 18 18" />
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
            <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.6 4.1 10 8a13.7 13.7 0 0 1-2.4 4" />
            <path d="M6.3 6.3A13.4 13.4 0 0 0 2 12c1.4 3.9 5 8 10 8 1.5 0 2.9-.4 4.1-1" />
          </svg>
        ) : (
          <svg aria-hidden="true" viewBox="0 0 24 24">
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        )}
      </button>
    </div>
  )
}

export function RestaurantLoginScreen({ onLogin }) {
  const [activeMode, setActiveMode] = useState('login')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showLoginPassword, setShowLoginPassword] = useState(false)
  const [showRegisterPassword, setShowRegisterPassword] = useState(false)
  const [registerForm, setRegisterForm] = useState(INITIAL_REGISTER_FORM)
  const [pendingUser, setPendingUser] = useState(null)
  const [setupMode, setSetupMode] = useState('existing-chain')
  const [restaurantForm, setRestaurantForm] = useState(INITIAL_RESTAURANT_FORM)
  const [chainName, setChainName] = useState('')
  const [selectedChainId, setSelectedChainId] = useState('')
  const [chains, setChains] = useState([])
  const [chainPage, setChainPage] = useState(1)
  const [hasMoreChains, setHasMoreChains] = useState(false)
  const [loadingChains, setLoadingChains] = useState(false)
  const [loadingAction, setLoadingAction] = useState('')
  const [errorText, setErrorText] = useState('')
  const [setupErrorText, setSetupErrorText] = useState('')

  const hasChains = chains.length > 0

  const setupDescription = useMemo(() => {
    if (!pendingUser) return ''
    if (pendingUser.isChainManager) {
      return 'Este gestor já está associado a uma cadeia. Crie o primeiro restaurante para entrar no painel.'
    }

    return setupMode === 'new-chain'
      ? 'Ao criar uma nova cadeia, este utilizador passa a ser gestor de cadeia.'
      : 'Ao criar um restaurante numa cadeia existente, este utilizador passa a ser gestor local.'
  }, [pendingUser, setupMode])

  const dialogOpen = Boolean(pendingUser)

  const loadChains = useCallback(async ({ append = false, page = 1, syncMode = false } = {}) => {
    try {
      setLoadingChains(true)
      const nextChains = await searchRestaurantChains({
        pageNumber: page,
        pageSize: CHAIN_PAGE_SIZE,
      })

      setChains((current) => {
        return append
          ? [
              ...current,
              ...nextChains.filter((chain) => !current.some((entry) => entry.id === chain.id)),
            ]
          : nextChains
      })

      if (!append && nextChains.length === 0) {
        setSetupMode('new-chain')
      } else if (syncMode) {
        setSetupMode(nextChains.length > 0 ? 'existing-chain' : 'new-chain')
      }

      setChainPage(page)
      setHasMoreChains(nextChains.length === CHAIN_PAGE_SIZE)
    } catch {
      if (!append) {
        setChains([])
        setSelectedChainId('')
        setHasMoreChains(false)

        if (syncMode) {
          setSetupMode('new-chain')
        }
      }
    } finally {
      setLoadingChains(false)
    }
  }, [])

  useEffect(() => {
    loadChains({ append: false, page: 1, syncMode: !dialogOpen })
  }, [dialogOpen, loadChains])

  useEffect(() => {
    setSelectedChainId((current) => {
      if (current && (dialogOpen || chains.some((chain) => chain.id === current))) {
        return current
      }

      return chains[0]?.id || ''
    })
  }, [chains, dialogOpen])

  async function handleSubmit(event) {
    event.preventDefault()

    try {
      setLoadingAction('login')
      const session = await bootstrapRestaurantSession({
        email,
        password,
        restaurant: '',
        token: DEFAULT_TOKEN,
      })
      setErrorText('')
      if (session?.needsSetup) {
        setPendingUser(session.user)
        if (session.user?.chainId) {
          setSelectedChainId(session.user.chainId)
        }
        setSetupMode(session.user?.chainId || hasChains ? 'existing-chain' : 'new-chain')
        return
      }
      onLogin(session)
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoadingAction('')
    }
  }

  async function handleRegister(event) {
    event.preventDefault()

    try {
      setLoadingAction('register')
      const user = await registerRestaurantUser(registerForm)
      setErrorText('')
      setPendingUser(user)
      setRestaurantForm((current) => ({
        ...current,
        name: current.name || `${registerForm.name || user.name} - Restaurante`,
      }))
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoadingAction('')
    }
  }

  async function handleSetupSubmit(event) {
    event.preventDefault()

    try {
      setLoadingAction('setup')
      const session = await completeRestaurantOnboarding({
        user: pendingUser,
        mode: setupMode,
        chainId: selectedChainId,
        chainName,
        restaurant: restaurantForm,
        token: DEFAULT_TOKEN,
      })
      setSetupErrorText('')
      setPendingUser(null)
      setRegisterForm(INITIAL_REGISTER_FORM)
      onLogin(session)
    } catch (error) {
      setSetupErrorText(error.message)
    } finally {
      setLoadingAction('')
    }
  }

  function updateRegisterField(field, value) {
    setRegisterForm((current) => ({ ...current, [field]: value }))
  }

  function updateRestaurantField(field, value) {
    setRestaurantForm((current) => ({ ...current, [field]: value }))
  }

  function updateRestaurantLocation({ latitude, longitude }) {
    setRestaurantForm((current) => ({
      ...current,
      latitude: String(latitude.toFixed(6)),
      longitude: String(longitude.toFixed(6)),
    }))
  }

  return (
    <section className="rb-login-wrap">
      <div className="rb-login-card rb-login-card-wide">
        <div className="rb-login-tabs" role="tablist" aria-label="Acesso ao painel">
          <button
            type="button"
            className={activeMode === 'login' ? 'active' : ''}
            onClick={() => {
              setActiveMode('login')
              setErrorText('')
            }}
          >
            Entrar
          </button>
          <button
            type="button"
            className={activeMode === 'register' ? 'active' : ''}
            onClick={() => {
              setActiveMode('register')
              setErrorText('')
            }}
          >
            Criar utilizador
          </button>
        </div>

        {activeMode === 'login' ? (
          <>
            <h2>Entrar no painel do restaurante</h2>
            <p>Utilize email e palavra-passe. O restaurante é identificado automaticamente.</p>

            <form className="rb-login-form" onSubmit={handleSubmit}>
              <label>
                Email
                <input
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  type="email"
                  placeholder="manager@fastbite.pt"
                  required
                />
              </label>
              <label>
                Palavra-passe
                <PasswordField
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  visible={showLoginPassword}
                  onToggle={() => setShowLoginPassword((current) => !current)}
                />
              </label>

              <button type="submit" className="rb-primary" disabled={loadingAction !== ''}>
                {loadingAction === 'login' ? 'A entrar...' : 'Entrar'}
              </button>
            </form>
          </>
        ) : (
          <>
            <h2>Criar utilizador de restaurante</h2>
            <p>Depois de criar a conta, configure a cadeia e o restaurante numa janela.</p>

            <form className="rb-login-form" onSubmit={handleRegister}>
              <label>
                Nome
                <input
                  value={registerForm.name}
                  onChange={(event) => updateRegisterField('name', event.target.value)}
                  type="text"
                  placeholder="Ana Silva"
                  required
                />
              </label>
              <label>
                Email
                <input
                  value={registerForm.email}
                  onChange={(event) => updateRegisterField('email', event.target.value)}
                  type="email"
                  placeholder="ana@fastbite.pt"
                  required
                />
              </label>
              <label>
                Palavra-passe
                <PasswordField
                  value={registerForm.password}
                  onChange={(event) => updateRegisterField('password', event.target.value)}
                  visible={showRegisterPassword}
                  onToggle={() => setShowRegisterPassword((current) => !current)}
                />
              </label>

              <button type="submit" className="rb-primary" disabled={loadingAction !== ''}>
                {loadingAction === 'register' ? 'A criar...' : 'Criar utilizador'}
              </button>
            </form>
          </>
        )}

        {errorText ? <p className="rb-chat-error">{errorText}</p> : null}
      </div>

      {pendingUser ? (
        <div className="rb-dialog-backdrop">
          <form className="rb-dialog-card rb-dialog-card-wide" onSubmit={handleSetupSubmit}>
            <div className="rb-dialog-head">
              <h3>Criar estrutura do restaurante</h3>
              <button
                type="button"
                className="rb-dialog-close"
                onClick={() => setPendingUser(null)}
                aria-label="Fechar"
              >
                x
              </button>
            </div>
            <p className="rb-dialog-description">{setupDescription}</p>

            <div className="rb-dialog-body rb-create-modal-body">
              <div className="rb-setup-mode">
                <button
                  type="button"
                  className={setupMode === 'existing-chain' ? 'active' : ''}
                  disabled={!hasChains}
                  onClick={() => setSetupMode('existing-chain')}
                >
                  Restaurante numa cadeia
                </button>
                <button
                  type="button"
                  className={setupMode === 'new-chain' ? 'active' : ''}
                  onClick={() => setSetupMode('new-chain')}
                >
                  Nova cadeia + restaurante
                </button>
              </div>

              <div className="rb-login-form rb-create-product-modal-form">
                {setupMode === 'existing-chain' ? (
                  <>
                    <label>
                      Cadeia existente
                      <select
                        value={selectedChainId}
                        onChange={(event) => setSelectedChainId(event.target.value)}
                        required
                      >
                        {chains.length === 0 ? (
                          <option value="">
                            {loadingChains ? 'A carregar cadeias...' : 'Sem cadeias disponiveis'}
                          </option>
                        ) : null}
                        {chains.map((chain) => (
                          <option key={chain.id} value={chain.id}>
                            {chain.name}
                          </option>
                        ))}
                      </select>
                    </label>
                    {hasMoreChains ? (
                      <div style={{ marginTop: 8 }}>
                        <button
                          type="button"
                          className="rb-btn-outline"
                          onClick={() => loadChains({ append: true, page: chainPage + 1 })}
                          disabled={loadingChains}
                        >
                          {loadingChains ? 'A carregar...' : 'Carregar mais'}
                        </button>
                      </div>
                    ) : null}
                  </>
                ) : (
                  <label>
                    Nome da cadeia
                    <input
                      value={chainName}
                      onChange={(event) => setChainName(event.target.value)}
                      type="text"
                      placeholder="Ex.: Fastbite Norte"
                      required
                    />
                  </label>
                )}

                <div className="rb-login-grid">
                  <label>
                    Restaurante
                    <input
                      value={restaurantForm.name}
                      onChange={(event) => updateRestaurantField('name', event.target.value)}
                      type="text"
                      placeholder="Ex.: Fastbite Baixa"
                      required
                    />
                  </label>
                  <label>
                    Raio de entrega (km)
                    <input
                      value={restaurantForm.delivery_radius}
                      onChange={(event) => updateRestaurantField('delivery_radius', event.target.value)}
                      type="number"
                      min="0"
                      step="0.5"
                      required
                    />
                  </label>
                </div>

                <div className="rb-login-grid">
                  <label>
                    Abertura
                    <input
                      value={restaurantForm.opening_hours}
                      onChange={(event) => updateRestaurantField('opening_hours', event.target.value)}
                      type="time"
                      required
                    />
                  </label>
                  <label>
                    Fecho
                    <input
                      value={restaurantForm.closing_hours}
                      onChange={(event) => updateRestaurantField('closing_hours', event.target.value)}
                      type="time"
                      required
                    />
                  </label>
                </div>

                <div className="rb-login-grid">
                  <label>
                    Rua
                    <input
                      value={restaurantForm.street}
                      onChange={(event) => updateRestaurantField('street', event.target.value)}
                      type="text"
                      placeholder="Ex.: Rua das Flores 12"
                      required
                    />
                  </label>
                  <label>
                    Cidade
                    <input
                      value={restaurantForm.city}
                      onChange={(event) => updateRestaurantField('city', event.target.value)}
                      type="text"
                      placeholder="Ex.: Porto"
                      required
                    />
                  </label>
                </div>

                <div className="rb-login-grid">
                  <label>
                    Código postal
                    <input
                      value={restaurantForm.postal_code}
                      onChange={(event) => updateRestaurantField('postal_code', event.target.value)}
                      type="text"
                      placeholder="Ex.: 4000-000"
                      required
                    />
                  </label>
                  <label>
                    País
                    <input
                      value={restaurantForm.country}
                      onChange={(event) => updateRestaurantField('country', event.target.value)}
                      type="text"
                      required
                    />
                  </label>
                </div>

                <div className="rb-login-grid">
                  <label>
                    Latitude
                    <input
                      value={restaurantForm.latitude}
                      onChange={(event) => updateRestaurantField('latitude', event.target.value)}
                      type="number"
                      step="0.000001"
                      required
                    />
                  </label>
                  <label>
                    Longitude
                    <input
                      value={restaurantForm.longitude}
                      onChange={(event) => updateRestaurantField('longitude', event.target.value)}
                      type="number"
                      step="0.000001"
                      required
                    />
                  </label>
                </div>

                <RestaurantAddressMapPicker
                  latitude={restaurantForm.latitude}
                  longitude={restaurantForm.longitude}
                  onChange={updateRestaurantLocation}
                  height={220}
                />
              </div>

              {setupErrorText ? <p className="rb-chat-error">{setupErrorText}</p> : null}
            </div>

            <div className="rb-dialog-actions">
              <button type="button" className="rb-icon-mini" onClick={() => setPendingUser(null)}>
                Mais tarde
              </button>
              <button type="submit" className="rb-primary" disabled={loadingAction !== ''}>
                {loadingAction === 'setup' ? 'A criar...' : 'Criar e entrar'}
              </button>
            </div>
          </form>
        </div>
      ) : null}
    </section>
  )
}
