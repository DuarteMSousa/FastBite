import { useEffect, useMemo, useState } from 'react'
import {
  bootstrapRestaurantSession,
  completeRestaurantOnboarding,
  fetchAllRestaurantChains,
  registerRestaurantUser,
} from '../../../services/restaurantOpsService'

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

export function RestaurantLoginScreen({ onLogin }) {
  const [activeMode, setActiveMode] = useState('login')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [registerForm, setRegisterForm] = useState(INITIAL_REGISTER_FORM)
  const [pendingUser, setPendingUser] = useState(null)
  const [setupMode, setSetupMode] = useState('existing-chain')
  const [restaurantForm, setRestaurantForm] = useState(INITIAL_RESTAURANT_FORM)
  const [chainName, setChainName] = useState('')
  const [selectedChainId, setSelectedChainId] = useState('')
  const [chains, setChains] = useState([])
  const [loadingAction, setLoadingAction] = useState('')
  const [errorText, setErrorText] = useState('')
  const [setupErrorText, setSetupErrorText] = useState('')

  const hasChains = chains.length > 0

  const setupDescription = useMemo(() => {
    if (!pendingUser) return ''
    if (pendingUser.isChainManager) {
      return 'Este gestor ja esta associado a uma chain. Cria o primeiro restaurante para entrar no painel.'
    }

    return setupMode === 'new-chain'
      ? 'Ao criares uma nova chain, este utilizador passa a ser gestor de chain.'
      : 'Ao criares um restaurante numa chain existente, este utilizador passa a ser gestor local.'
  }, [pendingUser, setupMode])

  useEffect(() => {
    let cancelled = false

    async function loadChains() {
      try {
        const nextChains = await fetchAllRestaurantChains()
        if (cancelled) return
        setChains(nextChains)
        setSelectedChainId((current) => current || nextChains[0]?.id || '')
        if (nextChains.length === 0) {
          setSetupMode('new-chain')
        }
      } catch {
        if (!cancelled) {
          setChains([])
          setSetupMode('new-chain')
        }
      }
    }

    loadChains()

    return () => {
      cancelled = true
    }
  }, [])

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
            <p>Utiliza email e password. O restaurante e resolvido automaticamente.</p>

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
                Password
                <input
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  type="password"
                  placeholder="Password"
                  required
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
            <p>Depois de criar a conta, configuras logo a chain e o restaurante numa modal.</p>

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
                Password
                <input
                  value={registerForm.password}
                  onChange={(event) => updateRegisterField('password', event.target.value)}
                  type="password"
                  placeholder="Password"
                  required
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
                  Restaurante numa chain
                </button>
                <button
                  type="button"
                  className={setupMode === 'new-chain' ? 'active' : ''}
                  onClick={() => setSetupMode('new-chain')}
                >
                  Nova chain + restaurante
                </button>
              </div>

              <div className="rb-login-form rb-create-product-modal-form">
                {setupMode === 'existing-chain' ? (
                  <label>
                    Chain existente
                    <select
                      value={selectedChainId}
                      onChange={(event) => setSelectedChainId(event.target.value)}
                      required
                    >
                      {chains.map((chain) => (
                        <option key={chain.id} value={chain.id}>
                          {chain.name}
                        </option>
                      ))}
                    </select>
                  </label>
                ) : (
                  <label>
                    Nome da chain
                    <input
                      value={chainName}
                      onChange={(event) => setChainName(event.target.value)}
                      type="text"
                      placeholder="FastBite Norte"
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
                      placeholder="FastBite Baixa"
                      required
                    />
                  </label>
                  <label>
                    Raio de entrega km
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
                      placeholder="Rua das Flores 12"
                      required
                    />
                  </label>
                  <label>
                    Cidade
                    <input
                      value={restaurantForm.city}
                      onChange={(event) => updateRestaurantField('city', event.target.value)}
                      type="text"
                      placeholder="Porto"
                      required
                    />
                  </label>
                </div>

                <div className="rb-login-grid">
                  <label>
                    Codigo postal
                    <input
                      value={restaurantForm.postal_code}
                      onChange={(event) => updateRestaurantField('postal_code', event.target.value)}
                      type="text"
                      placeholder="4000-000"
                      required
                    />
                  </label>
                  <label>
                    Pais
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
