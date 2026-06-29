import type { FormEvent } from 'react'
import './search.css'

type SearchResult = {
  id: string
  sourceModule: string
  sourceType: string
  title: string
  detail: string
  date: string | null
  targetUrl: string
}

type Props = {
  searchQuery: string
  setSearchQuery: (value: string) => void
  searchResponse: { query: string; results: SearchResult[]; grouped: Record<string, number> }
  searchGroups: Array<{ module: string; results: SearchResult[] }>
  modules: string[]
  householdReady: boolean
  searchEverything: (event?: FormEvent<HTMLFormElement>) => void
  openTargetUrl: (targetUrl: string) => void
}

export function SearchPage({ searchQuery, setSearchQuery, searchResponse, searchGroups, modules, householdReady, searchEverything, openTargetUrl }: Props) {
  return (
    <section className="search-panel page-card">
      <div className="section-heading">
        <div>
          <p className="eyebrow">Global Search</p>
          <h2>Search across Home OS.</h2>
        </div>
      </div>

      <form className="setup-form search-form" onSubmit={searchEverything}>
        <label>
          Search
          <input
            value={searchQuery}
            onChange={(event) => setSearchQuery(event.target.value)}
            placeholder="OBI, LDL, warranty, filter, invoice..."
            minLength={2}
          />
        </label>
        <button type="submit" disabled={!householdReady || searchQuery.trim().length < 2}>
          Search
        </button>
      </form>

      <div className="search-summary-grid">
        {modules.map((module) => (
          <article key={module}>
            <span>{module}</span>
            <strong>{searchResponse.grouped[module] ?? 0}</strong>
          </article>
        ))}
      </div>

      <div className="search-results">
        {searchResponse.query && searchResponse.results.length === 0 && (
          <p className="empty-state">No results for “{searchResponse.query}”. Try a merchant, document title, marker name, task area, or reminder note.</p>
        )}

        {!searchResponse.query && (
          <p className="empty-state">Search expenses, health markers, home tasks, reminders, and documents from one place.</p>
        )}

        {searchGroups.map((group) => (
          <section className="search-result-group" key={group.module}>
            <div className="panel-heading-row">
              <div>
                <p className="eyebrow">{group.module}</p>
                <h3>{group.results.length} result{group.results.length === 1 ? '' : 's'}</h3>
              </div>
            </div>

            <div className="search-result-list">
              {group.results.map((result) => (
                <button className="search-result-item" type="button" onClick={() => openTargetUrl(result.targetUrl)} key={result.id}>
                  <span>
                    <strong>{result.title}</strong>
                    <small>{result.sourceType} · {result.detail}</small>
                  </span>
                  <em>{result.date ?? 'open'}</em>
                </button>
              ))}
            </div>
          </section>
        ))}
      </div>
    </section>
  )
}
