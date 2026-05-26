import { useEffect, useMemo, useRef, useState } from 'react'
import { Navigate, Route, Routes, useLocation, useNavigate } from 'react-router-dom'
import { PageContainer } from '../../components/layout/PageContainer'
import { RESTAURANT_VIEWS, viewByPath, viewPath } from '../../features/restaurant/views'
import { RestaurantSideNav } from '../../features/restaurant/components/RestaurantSideNav'
import { RestaurantLoginScreen } from '../../features/restaurant/screens/RestaurantLoginScreen'
import { ConfirmDialog } from '../../components/common/ConfirmDialog'
import { disconnectEchoClient } from '../../services/realtime/echoClient'
import { refreshRestaurantSessionAccess } from '../../services/restaurantOpsService'

const SESSION_STORAGE_KEY = 'fastbite_restaurant_session'

function loadStoredSession() {
  try {
    const raw = window.localStorage.getItem(SESSION_STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export function RestaurantWebShell() {
  const location = useLocation()
  const navigate = useNavigate()
  const [session, setSession] = useState(loadStoredSession)
  const [selectedOrderId, setSelectedOrderId] = useState('')
  const selectedOrderIdRef = useRef('')
  const [logoutConfirmOpen, setLogoutConfirmOpen] = useState(false)

  const isChainManager = Boolean(session?.isChainManager)

  const routePath = location.pathname.replace(/^\/restaurant\/?/, '')
  const activeView = useMemo(() => {
    const fallback = RESTAURANT_VIEWS[0]
    const candidate = viewByPath(routePath)
    if (!candidate) return fallback
    if (candidate.chainOnly && !isChainManager) return fallback
    return candidate
  }, [routePath, isChainManager])

  const accessibleViews = useMemo(
    () => RESTAURANT_VIEWS.filter((view) => !view.chainOnly || isChainManager),
    [isChainManager],
  )

  const selectedOrderIdFromUrl = useMemo(() => {
    const params = new URLSearchParams(location.search)
    return params.get('orderId') ?? ''
  }, [location.search])

  useEffect(() => {
    if (session) {
      window.localStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify(session))
      return
    }

    window.localStorage.removeItem(SESSION_STORAGE_KEY)
  }, [session])

  useEffect(() => {
    if (!selectedOrderIdFromUrl || selectedOrderIdFromUrl === selectedOrderIdRef.current) return
    selectedOrderIdRef.current = selectedOrderIdFromUrl
    setSelectedOrderId(selectedOrderIdFromUrl)
  }, [selectedOrderIdFromUrl])

  useEffect(() => {
    const storedSession = loadStoredSession()
    if (!storedSession?.devUserId && !storedSession?.userId) return undefined

    let cancelled = false

    async function refreshStoredSession() {
      try {
        const nextSession = await refreshRestaurantSessionAccess(storedSession)
        if (!cancelled) {
          setSession(nextSession)
        }
      } catch {
        if (!cancelled) {
          setSession(null)
        }
      }
    }

    refreshStoredSession()

    return () => {
      cancelled = true
    }
  }, [])

  function handleLogin(nextSession) {
    setSession(nextSession)
    setSelectedOrderId('')
    selectedOrderIdRef.current = ''
    navigate('/restaurant/dashboard', { replace: true })
  }

  function handleLogout() {
    setLogoutConfirmOpen(true)
  }

  function confirmLogout() {
    disconnectEchoClient()
    setSession(null)
    setSelectedOrderId('')
    selectedOrderIdRef.current = ''
    setLogoutConfirmOpen(false)
    navigate('/restaurant/login', { replace: true })
  }

  function handleSelectOrder(orderId) {
    selectedOrderIdRef.current = orderId
    setSelectedOrderId(orderId)
  }

  function navigateToView(viewId) {
    const nextPath = viewPath(viewId)
    const orderScoped = viewId === 'chat' || viewId === 'order-detail'
    const orderId = selectedOrderIdRef.current
    const search = orderScoped && orderId ? `?orderId=${encodeURIComponent(orderId)}` : ''
    navigate(`/restaurant/${nextPath}${search}`)
  }

  if (!session) {
    return (
      <PageContainer restaurantUnit="Acesso de equipa">
        <Routes>
          <Route path="login" element={<RestaurantLoginScreen onLogin={handleLogin} />} />
          <Route path="*" element={<Navigate to="/restaurant/login" replace />} />
        </Routes>
      </PageContainer>
    )
  }

  if (routePath === 'login') {
    return <Navigate to="/restaurant/dashboard" replace />
  }

  return (
    <PageContainer
      restaurantUnit={`Unidade ${session.restaurant}`}
      topbarActions={
        <button
          type="button"
          className={`rb-store-profile-btn ${activeView.id === 'profile' ? 'active' : ''}`}
          onClick={() => navigateToView('profile')}
          aria-label="Abrir perfil do restaurante"
          title="Perfil do restaurante"
        >
          <svg aria-hidden="true" viewBox="0 0 24 24">
            <path d="M4 10h16l-1.1-5.5A1.9 1.9 0 0 0 17 3H7a1.9 1.9 0 0 0-1.9 1.5L4 10Z" />
            <path d="M5 10v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9" />
            <path d="M9 21v-7h6v7" />
            <path d="M4 10c.6 1.3 2.7 1.3 3.3 0 .6 1.3 2.7 1.3 3.3 0 .6 1.3 2.7 1.3 3.3 0 .6 1.3 2.7 1.3 3.3 0 .6 1.3 2.7 1.3 3.3 0" />
          </svg>
        </button>
      }
    >
      <div className="rb-shell">
        <RestaurantSideNav
          views={RESTAURANT_VIEWS}
          operatorName={session.operatorName}
          onLogout={handleLogout}
          session={session}
        />
        <section className="rb-main">
          <Routes>
            <Route index element={<Navigate to={viewPath('dashboard')} replace />} />
            {accessibleViews.map((view) => {
              const Screen = view.Component

              return (
                <Route
                  key={view.id}
                  path={view.path}
                  element={
                    <Screen
                      session={session}
                      selectedOrderId={selectedOrderId}
                      onSelectOrder={handleSelectOrder}
                      onNavigate={navigateToView}
                      onSessionChange={setSession}
                    />
                  }
                />
              )
            })}
            <Route path="*" element={<Navigate to={viewPath('dashboard')} replace />} />
          </Routes>
        </section>
      </div>

      <ConfirmDialog
        open={logoutConfirmOpen}
        title="Terminar sessão?"
        description="Irá sair da conta. Terá de iniciar sessão novamente."
        confirmLabel="Sair"
        destructive
        onCancel={() => setLogoutConfirmOpen(false)}
        onConfirm={confirmLogout}
      />
    </PageContainer>
  )
}
