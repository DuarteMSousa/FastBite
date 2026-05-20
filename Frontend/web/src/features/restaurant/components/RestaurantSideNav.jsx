import { NavIcon } from './NavIcon'

export function RestaurantSideNav({ views, activeViewId, onSelect, operatorName, onLogout, session }) {
  const isChainManager = Boolean(session?.isChainManager)
  const visibleViews = views.filter((view) => {
    if (view.hideFromNav) return false
    if (view.chainOnly && !isChainManager) return false
    return true
  })

  const profileLabel = isChainManager ? 'Gestor de cadeia' : 'Gestor local'

  return (
    <aside className="rb-sidebar">
      {visibleViews.map((view) => (
        <button
          key={view.id}
          type="button"
          className={`rb-sidebar-item ${view.id === activeViewId ? 'active' : ''}`}
          onClick={() => onSelect(view.id)}
        >
          <span className="rb-sidebar-icon">
            <NavIcon id={view.id} size={20} />
          </span>
          <span>{view.label}</span>
          {view.badge ? <span className="rb-sidebar-badge">{view.badge}</span> : null}
        </button>
      ))}
      <div className="rb-sidebar-status">
        <p>
          <strong>{session?.restaurant ?? 'Unidade'}</strong>
          <br />
          {profileLabel}
        </p>
      </div>
      <div className="rb-sidebar-footer">
        <p>
          Sessao: <strong>{operatorName}</strong>
        </p>
        <button type="button" className="rb-link-btn" onClick={onLogout}>
          Terminar sessao
        </button>
      </div>
    </aside>
  )
}
