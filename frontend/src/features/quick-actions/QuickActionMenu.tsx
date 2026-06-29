import './quick-actions.css'

type QuickAction = {
  label: string
  detail: string
  onClick: () => void
}

type Props = {
  open: boolean
  actions: QuickAction[]
  onToggle: () => void
  onClose: () => void
}

export function QuickActionMenu({ open, actions, onToggle, onClose }: Props) {
  return (
    <div className={`quick-action-menu ${open ? 'open' : ''}`}>
      {open && (
        <div className="quick-action-list" role="menu" aria-label="Quick actions">
          {actions.map((action) => (
            <button
              type="button"
              role="menuitem"
              key={action.label}
              onClick={() => {
                action.onClick()
                onClose()
              }}
            >
              <strong>{action.label}</strong>
              <span>{action.detail}</span>
            </button>
          ))}
        </div>
      )}
      <button className="quick-action-fab" type="button" onClick={onToggle} aria-expanded={open} aria-label="Open quick actions">
        {open ? 'Close' : 'Add'}
      </button>
    </div>
  )
}
