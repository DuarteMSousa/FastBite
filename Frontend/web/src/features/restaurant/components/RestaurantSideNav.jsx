import { NavIcon } from './NavIcon'
import { NavLink } from 'react-router-dom'

export function RestaurantSideNav({ views, operatorName, onLogout, session }) {
  const isChainManager = Boolean(session?.isChainManager)
  const visibleViews = views.filter((view) => {
    if (view.hideFromNav) return false
    if (view.chainOnly && !isChainManager) return false
    return true
  })

  return (
    <aside className="rb-sidebar">
      {visibleViews.map((view) => (
        <NavLink
          key={view.id}
          to={`/restaurant/${view.path}`}
          className={({ isActive }) => `rb-sidebar-item ${isActive ? 'active' : ''}`}
        >
          <span className="rb-sidebar-icon">
            <NavIcon id={view.id} size={20} />
          </span>
          <span>{view.label}</span>
          {view.badge ? <span className="rb-sidebar-badge">{view.badge}</span> : null}
        </NavLink>
      ))}
      <div className="rb-sidebar-footer">
        <p>
          Sessão: <strong>{operatorName}</strong>
        </p>
        <button type="button" className="rb-link-btn" onClick={onLogout}>
          Terminar sessão
        </button>
      </div>
    </aside>
  )
}
