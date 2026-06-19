import { type FormEvent, useEffect, useState } from 'react'
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

type HouseholdMember = {
  id: string
  displayName: string
  memberType: 'adult' | 'child'
  birthDate: string | null
  color: string | null
  active: boolean
}

type Household = {
  id: string
  name: string
  defaultCurrency: string
  createdAt: string
  members: HouseholdMember[]
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

const householdStorageKey = 'home-os.household-id'

async function apiJson<T>(url: string, options?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
    ...options,
  })

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status}`)
  }

  return response.json() as Promise<T>
}

function App() {
  const [dashboard, setDashboard] = useState<Dashboard>(fallbackDashboard)
  const [apiState, setApiState] = useState<'checking' | 'online' | 'offline'>('checking')
  const [household, setHousehold] = useState<Household | null>(null)
  const [householdName, setHouseholdName] = useState('Home OS Household')
  const [memberName, setMemberName] = useState('')
  const [memberType, setMemberType] = useState<'adult' | 'child'>('adult')
  const [setupState, setSetupState] = useState<'idle' | 'saving' | 'error'>('idle')

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

  useEffect(() => {
    const storedHouseholdId = window.localStorage.getItem(householdStorageKey)

    if (!storedHouseholdId) {
      return
    }

    apiJson<Household>(`/api/households/${storedHouseholdId}`)
      .then(setHousehold)
      .catch(() => window.localStorage.removeItem(householdStorageKey))
  }, [])

  const createHousehold = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSetupState('saving')

    try {
      const created = await apiJson<{ id: string }>('/api/households', {
        method: 'POST',
        body: JSON.stringify({
          name: householdName,
          defaultCurrency: 'PLN',
        }),
      })
      const nextHousehold = await apiJson<Household>(`/api/households/${created.id}`)
      window.localStorage.setItem(householdStorageKey, created.id)
      setHousehold(nextHousehold)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const addMember = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/members`, {
        method: 'POST',
        body: JSON.stringify({
          displayName: memberName,
          memberType,
          color: memberType === 'adult' ? '#175c4a' : '#7b6a2d',
        }),
      })
      setHousehold(await apiJson<Household>(`/api/households/${household.id}`))
      setMemberName('')
      setMemberType('adult')
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const modules = [
    {
      title: 'Home',
      value: dashboard.summary.homeTasksDue,
      label: 'tasks due',
      detail: 'Maintenance, rooms, devices, inventory',
    },
    {
      title: 'Expenses',
      value: `${dashboard.summary.monthlySpend.toLocaleString('pl-PL')} PLN`,
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
            <span>Family control center</span>
          </div>
        </div>

        <nav className="nav-list" aria-label="Main navigation">
          <a href="#dashboard" className="active">Dashboard</a>
          <a href="#household">Household</a>
          <a href="#expenses">Expenses</a>
          <a href="#health">Health</a>
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

        <section className="setup-panel" id="household">
          <div>
            <p className="eyebrow">Household</p>
            <h2>{household ? household.name : 'Create your household.'}</h2>
            <p className="panel-copy">
              {household
                ? `${household.defaultCurrency} is set as the household currency.`
                : 'Start with the shared family space. Members can be adults now and children later.'}
            </p>
          </div>

          {!household ? (
            <form className="setup-form" onSubmit={createHousehold}>
              <label>
                Household name
                <input
                  value={householdName}
                  onChange={(event) => setHouseholdName(event.target.value)}
                  required
                />
              </label>
              <button type="submit" disabled={setupState === 'saving'}>
                Create household
              </button>
              {setupState === 'error' && <p className="form-error">Could not save household.</p>}
            </form>
          ) : (
            <div className="member-workspace">
              <div className="member-list">
                {household.members.length > 0 ? (
                  household.members.map((member) => (
                    <article className="member-item" key={member.id}>
                      <span style={{ background: member.color ?? '#175c4a' }}></span>
                      <div>
                        <strong>{member.displayName}</strong>
                        <small>{member.memberType}</small>
                      </div>
                    </article>
                  ))
                ) : (
                  <p className="empty-state">No family members yet.</p>
                )}
              </div>

              <form className="setup-form inline" onSubmit={addMember}>
                <label>
                  Member name
                  <input
                    value={memberName}
                    onChange={(event) => setMemberName(event.target.value)}
                    required
                  />
                </label>
                <label>
                  Type
                  <select value={memberType} onChange={(event) => setMemberType(event.target.value as 'adult' | 'child')}>
                    <option value="adult">Adult</option>
                    <option value="child">Child</option>
                  </select>
                </label>
                <button type="submit" disabled={setupState === 'saving'}>
                  Add member
                </button>
              </form>
              {setupState === 'error' && <p className="form-error">Could not save member.</p>}
            </div>
          )}
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
