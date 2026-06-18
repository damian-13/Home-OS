import { useEffect, useState } from 'react'
import './App.css'

type Dashboard = {
  app: string
  status: string
  summary: {
    homeTasksDue: number
    monthlySpend: number
    healthMarkersTracked: number
    documentsStored: number
  }
  attention: Array<{
    label: string
    area: string
    due: string
  }>
}

const fallbackDashboard: Dashboard = {
  app: 'Home OS',
  status: 'offline',
  summary: {
    homeTasksDue: 0,
    monthlySpend: 0,
    healthMarkersTracked: 0,
    documentsStored: 0,
  },
  attention: [],
}

function App() {
  const [dashboard, setDashboard] = useState<Dashboard>(fallbackDashboard)
  const [apiState, setApiState] = useState<'checking' | 'online' | 'offline'>('checking')

  useEffect(() => {
    fetch('/api/dashboard')
      .then((response) => {
        if (!response.ok) {
          throw new Error('Dashboard API failed')
        }

        return response.json() as Promise<Dashboard>
      })
      .then((data) => {
        setDashboard(data)
        setApiState('online')
      })
      .catch(() => {
        setApiState('offline')
      })
  }, [])

  const modules = [
    {
      title: 'Home',
      value: dashboard.summary.homeTasksDue,
      label: 'tasks due',
      detail: 'Maintenance, rooms, devices, inventory',
    },
    {
      title: 'Expenses',
      value: `€${dashboard.summary.monthlySpend.toLocaleString('en-US')}`,
      label: 'this month',
      detail: 'Budgets, bills, receipts, reports',
    },
    {
      title: 'Health',
      value: dashboard.summary.healthMarkersTracked,
      label: 'markers',
      detail: 'Blood tests, trends, documents',
    },
    {
      title: 'Documents',
      value: dashboard.summary.documentsStored,
      label: 'stored',
      detail: 'Contracts, manuals, invoices, lab PDFs',
    },
  ]

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <div className="brand">
          <span className="brand-mark">H</span>
          <div>
            <strong>{dashboard.app}</strong>
            <span>Personal control center</span>
          </div>
        </div>

        <nav className="nav-list" aria-label="Main navigation">
          <a href="#dashboard" className="active">Dashboard</a>
          <a href="#home">Home</a>
          <a href="#expenses">Expenses</a>
          <a href="#health">Blood tests</a>
          <a href="#documents">Documents</a>
        </nav>
      </aside>

      <section className="workspace" id="dashboard">
        <header className="topbar">
          <div>
            <p className="eyebrow">Today</p>
            <h1>Your home, money, and health in one place.</h1>
          </div>
          <div className={`api-pill ${apiState}`}>
            <span></span>
            API {apiState}
          </div>
        </header>

        <section className="module-grid" aria-label="Overview modules">
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
            <p className="eyebrow">Needs Attention</p>
            <h2>Start with the next important things.</h2>
          </div>

          <div className="attention-list">
            {dashboard.attention.length > 0 ? (
              dashboard.attention.map((item) => (
                <article className="attention-item" key={`${item.area}-${item.label}`}>
                  <div>
                    <strong>{item.label}</strong>
                    <span>{item.area}</span>
                  </div>
                  <time>{item.due}</time>
                </article>
              ))
            ) : (
              <article className="attention-item">
                <div>
                  <strong>Waiting for backend</strong>
                  <span>System</span>
                </div>
                <time>Now</time>
              </article>
            )}
          </div>
        </section>
      </section>
    </main>
  )
}

export default App
