import type { AppPage } from '../../shared/navigation'
import './mobile-navigation.css'

type MobileNavItem = {
  page: AppPage
  label: string
  shortLabel: string
}

type Props = {
  items: MobileNavItem[]
  activePage: AppPage
  onNavigate: (page: AppPage) => void
}

export function MobileNavigation({ items, activePage, onNavigate }: Props) {
  return (
    <nav className="mobile-bottom-nav" aria-label="Mobile primary navigation">
      {items.map((item) => (
        <button
          type="button"
          className={activePage === item.page ? 'active' : ''}
          aria-current={activePage === item.page ? 'page' : undefined}
          onClick={() => onNavigate(item.page)}
          key={item.page}
        >
          <span>{item.shortLabel}</span>
          <small>{item.label}</small>
        </button>
      ))}
    </nav>
  )
}
