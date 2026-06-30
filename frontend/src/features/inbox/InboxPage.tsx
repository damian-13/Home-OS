import { useMemo, useState } from 'react'
import './inbox.css'

type InboxItem = {
  id: string
  sourceModule: 'expenses' | 'health' | 'home' | 'reminders' | 'documents'
  sourceType: string
  severity: 'critical' | 'warning' | 'info'
  title: string
  detail: string
  targetAction: string
  targetPage: 'dashboard' | 'household' | 'home' | 'reminders' | 'inbox' | 'search' | 'timeline' | 'expenses' | 'health' | 'health-review' | 'documents'
  targetSection: 'overview' | 'monthly-review' | 'analytics' | 'transactions' | 'import-review' | 'budgets' | 'bills' | null
  detectedAt: string
  dueAt: string | null
  actionLabel?: string
}

type DailyReviewItem = Pick<InboxItem, 'id' | 'severity' | 'title' | 'detail' | 'targetPage' | 'targetSection'> & {
  actionLabel: string
}

type Inbox = {
  items: InboxItem[]
  summary: {
    total: number
    critical: number
    warning: number
    info: number
    highestSeverity: 'critical' | 'warning' | 'info' | null
  }
}

type SourceTotals = Record<'expenses' | 'health' | 'home' | 'reminders' | 'documents', number>

type Props = {
  inbox: Inbox
  inboxSourceTotals: SourceTotals
  dailyReviewItems: DailyReviewItem[]
  setupState: string
  onRefresh: () => void
  openInboxTarget: (item: Pick<InboxItem, 'targetPage' | 'targetSection'>) => void
}

const sourceFilters: Array<'all' | InboxItem['sourceModule']> = ['all', 'expenses', 'health', 'home', 'reminders', 'documents']
const severityFilters: Array<'all' | InboxItem['severity']> = ['all', 'critical', 'warning', 'info']
const shouldCollapseDetail = (item: InboxItem) => item.sourceModule === 'expenses' && item.detail.length > 96

export function InboxPage({ inbox, inboxSourceTotals, dailyReviewItems, setupState, onRefresh, openInboxTarget }: Props) {
  const [sourceFilter, setSourceFilter] = useState<'all' | InboxItem['sourceModule']>('all')
  const [severityFilter, setSeverityFilter] = useState<'all' | InboxItem['severity']>('all')

  const filteredItems = useMemo(() => inbox.items.filter((item) => (
    (sourceFilter === 'all' || item.sourceModule === sourceFilter)
    && (severityFilter === 'all' || item.severity === severityFilter)
  )), [inbox.items, sourceFilter, severityFilter])

  const inboxGroups = {
    critical: filteredItems.filter((item) => item.severity === 'critical'),
    warning: filteredItems.filter((item) => item.severity === 'warning'),
    info: filteredItems.filter((item) => item.severity === 'info'),
  }

  return (
    <section className="inbox-panel page-card">
      <div className="section-heading">
        <div>
          <p className="eyebrow">Daily Review</p>
          <h2>Start with what needs a decision.</h2>
        </div>
        <button type="button" onClick={onRefresh}>
          Refresh
        </button>
      </div>

      <div className="inbox-summary-grid">
        <article className={inbox.summary.critical > 0 ? 'danger' : 'good'}>
          <span>Total</span>
          <strong>{inbox.summary.total}</strong>
          <small>{inbox.summary.highestSeverity ?? 'calm'}</small>
        </article>
        <article><span>Expenses</span><strong>{inboxSourceTotals.expenses}</strong></article>
        <article><span>Health</span><strong>{inboxSourceTotals.health}</strong></article>
        <article><span>Home</span><strong>{inboxSourceTotals.home}</strong></article>
        <article><span>Reminders</span><strong>{inboxSourceTotals.reminders}</strong></article>
      </div>

      <section className="daily-review-panel">
        <div>
          <p className="eyebrow">Today</p>
          <h3>Daily review checklist</h3>
          <p className="panel-copy">Dashboard actions and high-priority Inbox items are shown here first.</p>
        </div>

        <div className="daily-review-list">
          {dailyReviewItems.length > 0 ? (
            dailyReviewItems.map((item) => (
              <article className={`daily-review-item severity-${item.severity}`} key={item.id}>
                <div>
                  <strong>{item.title}</strong>
                  <small>{item.detail}</small>
                </div>
                <button type="button" onClick={() => openInboxTarget(item)}>
                  {item.actionLabel}
                </button>
              </article>
            ))
          ) : (
            <p className="empty-state">Everything important looks calm for today.</p>
          )}
        </div>
      </section>

      <section className="inbox-items-panel">
        <div className="panel-heading-row">
          <div>
            <p className="eyebrow">Inbox</p>
            <h3>Review queue by urgency</h3>
            <p className="panel-copy">Use filters to process one kind of decision at a time.</p>
          </div>
        </div>

        <div className="inbox-filter-bar" aria-label="Inbox filters">
          <label>
            Source
            <select value={sourceFilter} onChange={(event) => setSourceFilter(event.target.value as typeof sourceFilter)}>
              {sourceFilters.map((source) => <option value={source} key={source}>{source}</option>)}
            </select>
          </label>
          <label>
            Severity
            <select value={severityFilter} onChange={(event) => setSeverityFilter(event.target.value as typeof severityFilter)}>
              {severityFilters.map((severity) => <option value={severity} key={severity}>{severity}</option>)}
            </select>
          </label>
          <button type="button" onClick={() => { setSourceFilter('all'); setSeverityFilter('all') }}>
            Reset
          </button>
        </div>

        <div className="inbox-group-list">
          {(Object.keys(inboxGroups) as Array<InboxItem['severity']>).map((severity) => (
            <section className={`inbox-group severity-${severity}`} key={severity}>
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">{severity}</p>
                  <h3>{inboxGroups[severity].length} item{inboxGroups[severity].length === 1 ? '' : 's'}</h3>
                </div>
              </div>

              <div className="inbox-item-list">
                {inboxGroups[severity].length > 0 ? (
                  inboxGroups[severity].map((item) => (
                    <article className={`inbox-item source-${item.sourceModule}`} key={item.id}>
                      <span></span>
                      <div>
                        <strong>{item.title}</strong>
                        <small>
                          {item.sourceModule} · {item.sourceType.replaceAll('_', ' ')}
                          {item.dueAt ? ` · due ${item.dueAt}` : ` · detected ${item.detectedAt.slice(0, 10)}`}
                        </small>
                        {shouldCollapseDetail(item) ? (
                          <details className="inbox-item-detail">
                            <summary>Details</summary>
                            <p>{item.detail}</p>
                          </details>
                        ) : (
                          <p>{item.detail}</p>
                        )}
                      </div>
                      <button type="button" onClick={() => openInboxTarget(item)}>
                        {item.targetAction}
                      </button>
                    </article>
                  ))
                ) : (
                  <p className="empty-state">No {severity} items match these filters.</p>
                )}
              </div>
            </section>
          ))}
        </div>
      </section>

      {setupState === 'error' && <p className="form-error">Could not load Inbox data.</p>}
    </section>
  )
}
