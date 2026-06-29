import './timeline.css'

type TimelineItem = {
  id: string
  sourceModule: string
  eventType: string
  title: string
  detail: string
  occurredAt: string
  targetUrl: string
  importance: 'low' | 'normal' | 'high'
}

type Props = {
  modules: string[]
  grouped: Record<string, number>
  moduleFilter: string
  setModuleFilter: (value: string) => void
  items: TimelineItem[]
  openTargetUrl: (targetUrl: string) => void
}

export function TimelinePage({ modules, grouped, moduleFilter, setModuleFilter, items, openTargetUrl }: Props) {
  return (
    <section className="timeline-panel page-card">
      <div className="section-heading">
        <div>
          <p className="eyebrow">History</p>
          <h2>Important household timeline.</h2>
          <p className="panel-copy">Routine low-value transactions are grouped so important events stay visible.</p>
        </div>
        <select value={moduleFilter} onChange={(event) => setModuleFilter(event.target.value)}>
          <option value="">All modules</option>
          {modules.map((module) => (
            <option value={module} key={module}>{module}</option>
          ))}
        </select>
      </div>

      <div className="timeline-summary-grid">
        {modules.map((module) => (
          <article key={module}>
            <span>{module}</span>
            <strong>{grouped[module] ?? 0}</strong>
          </article>
        ))}
      </div>

      <div className="timeline-list">
        {items.length > 0 ? (
          items.map((item) => (
            <button className={`timeline-item ${item.importance}`} type="button" onClick={() => openTargetUrl(item.targetUrl)} key={item.id}>
              <span className="timeline-dot"></span>
              <span>
                <strong>{item.title}</strong>
                <small>{item.sourceModule} · {item.eventType} · {item.detail}</small>
              </span>
              <em>{item.occurredAt.slice(0, 10)}</em>
            </button>
          ))
        ) : (
          <p className="empty-state">No important timeline events match this filter yet.</p>
        )}
      </div>
    </section>
  )
}
