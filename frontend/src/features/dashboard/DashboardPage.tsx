import type { ReactNode } from 'react'
import './dashboard.css'

type AttentionItem = {
  id: string
  area: 'home' | 'reminders' | 'expenses' | 'health' | 'documents'
  severity: 'critical' | 'warning' | 'info'
  title: string
  detail: string
  actionLabel: string
  targetPage: 'dashboard' | 'household' | 'home' | 'reminders' | 'inbox' | 'search' | 'timeline' | 'expenses' | 'health' | 'health-review' | 'documents'
  targetSection: 'overview' | 'monthly-review' | 'analytics' | 'transactions' | 'import-review' | 'budgets' | 'bills' | null
}

type ModuleCard = {
  title: string
  value: string | number
  label: string
  detail: string
}

type DailyAction = {
  title: string
  detail: string
  tone: string
  action: string
  onClick: () => void
}

type Props = {
  modules: ModuleCard[]
  quickCapture: ReactNode
  dailyActionCards: DailyAction[]
  attention: AttentionItem[]
  attentionGroups: Record<'critical' | 'warning' | 'info', AttentionItem[]>
  attentionGroupLabels: Record<'critical' | 'warning' | 'info', string>
  openAttentionTarget: (item: AttentionItem) => void
}

export function DashboardPage({
  modules,
  quickCapture,
  dailyActionCards,
  attention,
  attentionGroups,
  attentionGroupLabels,
  openAttentionTarget,
}: Props) {
  const todayActions = attention.slice(0, 3)
  const reviewActions = dailyActionCards.filter((item) => ['Inbox review', 'Review imported money', 'Health review'].includes(item.title))
  const recentActions = dailyActionCards.filter((item) => ['Recent household activity', 'Search everything', 'Documents expiring', 'Reminders due', 'Bills this month', 'Monthly money review'].includes(item.title))

  return (
    <section className="page-stack dashboard-decision-center">
      <section className="decision-section today-section" aria-label="Today">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Today</p>
            <h2>What should I do today?</h2>
            <p className="panel-copy">Start with the highest-value actions. Everything else can wait.</p>
          </div>
        </div>

        <div className="today-action-list">
          {todayActions.length > 0 ? (
            todayActions.map((item) => (
              <article className={`attention-item severity-${item.severity}`} key={item.id}>
                <div>
                  <strong>{item.title}</strong>
                  <span>{item.area} · {item.detail}</span>
                </div>
                <button type="button" onClick={() => openAttentionTarget(item)}>
                  {item.actionLabel}
                </button>
              </article>
            ))
          ) : (
            <article className="attention-item">
              <div>
                <strong>Everything important looks calm.</strong>
                <span>Add a quick expense, review recent activity, or set up the next useful reminder.</span>
              </div>
            </article>
          )}
        </div>
      </section>

      <section className="decision-section quick-capture-section" aria-label="Quick Capture">
        {quickCapture}
      </section>

      <section className="decision-section review-section" aria-label="Review">
        <div>
          <p className="eyebrow">Review</p>
          <h2>Clear the queue.</h2>
          <p className="panel-copy">Shortcuts for the places where Home OS needs your decision.</p>
        </div>
        <div className="daily-action-list">
          {reviewActions.map((item) => (
            <button className={`daily-action-item ${item.tone}`} type="button" onClick={item.onClick} key={item.title}>
              <span>
                <strong>{item.title}</strong>
                <small>{item.detail}</small>
              </span>
              <b>{item.action}</b>
            </button>
          ))}
        </div>
      </section>

      <section className="decision-section recent-section" aria-label="Recent Activity">
        <div>
          <p className="eyebrow">Recent Activity</p>
          <h2>Find or inspect context.</h2>
        </div>
        <div className="daily-action-list">
          {recentActions.map((item) => (
            <button className={`daily-action-item ${item.tone}`} type="button" onClick={item.onClick} key={item.title}>
              <span>
                <strong>{item.title}</strong>
                <small>{item.detail}</small>
              </span>
              <b>{item.action}</b>
            </button>
          ))}
        </div>
      </section>

      <section className="module-grid compact-modules" aria-label="Overview modules">
        {modules.map((module) => (
          <article className="module-card" key={module.title}>
            <span>{module.title}</span>
            <strong>{module.value}</strong>
            <small>{module.label}</small>
            <p>{module.detail}</p>
          </article>
        ))}
      </section>

      <section className="focus-panel">
        <div>
          <p className="eyebrow">All Attention</p>
          <h2>Everything waiting behind today.</h2>
        </div>

        <div className="attention-list">
          {attention.length > 0 ? (
            (Object.keys(attentionGroups) as Array<'critical' | 'warning' | 'info'>).map((severity) => (
              attentionGroups[severity].length > 0 && (
                <section className="attention-group" key={severity}>
                  <h3>{attentionGroupLabels[severity]}</h3>
                  {attentionGroups[severity].map((item) => (
                    <article className={`attention-item severity-${item.severity}`} key={item.id}>
                      <div>
                        <strong>{item.title}</strong>
                        <span>{item.area} · {item.detail}</span>
                      </div>
                      <button type="button" onClick={() => openAttentionTarget(item)}>
                        {item.actionLabel}
                      </button>
                    </article>
                  ))}
                </section>
              )
            ))
          ) : (
            <article className="attention-item">
              <div>
                <strong>Everything important looks calm.</strong>
                <span>No urgent review items right now.</span>
              </div>
            </article>
          )}
        </div>
      </section>
    </section>
  )
}
