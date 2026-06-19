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
  attention: Array<{ label: string; area: string; due: string }>
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

type CurrentUser = {
  id: string
  email: string
  displayName: string
  householdId: string
  linkedMemberId: string | null
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
    credentials: 'same-origin',
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
  const [sessionState, setSessionState] = useState<'checking' | 'ready'>('checking')
  const [currentUser, setCurrentUser] = useState<CurrentUser | null>(null)
  const [household, setHousehold] = useState<Household | null>(null)
  const [householdName, setHouseholdName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [displayName, setDisplayName] = useState('')
  const [memberName, setMemberName] = useState('')
  const [memberType, setMemberType] = useState<'adult' | 'child'>('adult')
  const [setupState, setSetupState] = useState<'idle' | 'saving' | 'error'>('idle')
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login')

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
    apiJson<{ user: CurrentUser | null }>('/api/auth/me')
      .then(({ user }) => {
        setCurrentUser(user)

        if (!user) {
          return
        }

        window.localStorage.setItem(householdStorageKey, user.householdId)
        return apiJson<Household>(`/api/households/${user.householdId}`).then(setHousehold)
      })
      .catch(() => {
        setCurrentUser(null)
        window.localStorage.removeItem(householdStorageKey)
      })
      .finally(() => {
        setSessionState('ready')
      })
  }, [])

  const register = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSetupState('saving')

    try {
      await apiJson<{ id: string }>('/api/auth/register', {
        method: 'POST',
        body: JSON.stringify({ email, password, displayName, householdName }),
      })
      await login()
    } catch {
      setSetupState('error')
    }
  }

  const login = async (event?: FormEvent<HTMLFormElement>) => {
    event?.preventDefault()
    setSetupState('saving')

    try {
      await apiJson<{ message: string }>('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      })
      const { user } = await apiJson<{ user: CurrentUser }>('/api/auth/me')
      const nextHousehold = await apiJson<Household>(`/api/households/${user.householdId}`)
      window.localStorage.setItem(householdStorageKey, user.householdId)
      setCurrentUser(user)
      setHousehold(nextHousehold)
      setPassword('')
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const logout = async () => {
    await fetch('/api/auth/logout', { credentials: 'same-origin' }).catch(() => undefined)
    setCurrentUser(null)
    setHousehold(null)
    window.localStorage.removeItem(householdStorageKey)
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

  if (sessionState === 'checking') {
    return (
      <main className="auth-page">
        <section className="auth-loading" aria-label="Loading Home OS">
          <span className="brand-mark">H</span>
          <strong>Opening Home OS</strong>
        </section>
      </main>
    )
  }

  if (!currentUser) {
    return (
      <main className="auth-page">
        <section className="auth-hero">
          <div className="brand auth-brand">
            <span className="brand-mark">H</span>
            <div>
              <strong>{dashboard.app}</strong>
              <span>Private family control center</span>
            </div>
          </div>

          <div className="auth-copy">
            <p className="eyebrow">Home, money, health</p>
            <h1>One calm command center for your family life.</h1>
            <p>
              Track household members, bills, health records, documents, and future Home Assistant devices from one private app.
            </p>
          </div>

          <div className="auth-preview" aria-label="Home OS preview">
            <article>
              <span>Expenses</span>
              <strong>1 284 PLN</strong>
              <small>monthly view</small>
            </article>
            <article>
              <span>Health</span>
              <strong>12</strong>
              <small>markers tracked</small>
            </article>
            <article>
              <span>Home</span>
              <strong>3</strong>
              <small>tasks due</small>
            </article>
          </div>
        </section>

        <section className="auth-card" aria-label="Authentication">
          <div>
            <p className="eyebrow">{authMode === 'login' ? 'Welcome Back' : 'First Setup'}</p>
            <h2>{authMode === 'login' ? 'Log in to Home OS' : 'Create your Home OS account'}</h2>
          </div>

          <div className="auth-tabs" role="tablist" aria-label="Authentication mode">
            <button className={authMode === 'login' ? 'active' : ''} type="button" onClick={() => setAuthMode('login')}>
              Log in
            </button>
            <button className={authMode === 'register' ? 'active' : ''} type="button" onClick={() => setAuthMode('register')}>
              Register
            </button>
          </div>

          {authMode === 'login' ? (
            <form className="setup-form auth-form" onSubmit={login}>
              <label>
                Email
                <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
              </label>
              <label>
                Password
                <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
              </label>
              <button type="submit" disabled={setupState === 'saving'}>
                {setupState === 'saving' ? 'Logging in...' : 'Log in'}
              </button>
            </form>
          ) : (
            <form className="setup-form auth-form" onSubmit={register}>
              <label>
                Display name
                <input value={displayName} onChange={(event) => setDisplayName(event.target.value)} required />
              </label>
              <label>
                Email
                <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
              </label>
              <label>
                Password
                <input
                  type="password"
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  minLength={8}
                  required
                />
              </label>
              <label>
                Household name
                <input value={householdName} onChange={(event) => setHouseholdName(event.target.value)} required />
              </label>
              <button type="submit" disabled={setupState === 'saving'}>
                {setupState === 'saving' ? 'Creating account...' : 'Create account'}
              </button>
            </form>
          )}

          {setupState === 'error' && <p className="form-error">Could not authenticate. Check the details and try again.</p>}
          <div className={`api-pill ${apiState}`}>
            <span></span>
            API {apiState}
          </div>
        </section>
      </main>
    )
  }

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <div className="brand">
          <span className="brand-mark">H</span>
          <div>
            <strong>{dashboard.app}</strong>
            <span>{currentUser.displayName}</span>
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
            <h1>Welcome back, {currentUser.displayName}.</h1>
          </div>
          <button className="logout-button" type="button" onClick={logout}>
            Log out
          </button>
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
            <h2>{household?.name ?? 'Loading household...'}</h2>
            <p className="panel-copy">
              {household
                ? `${household.defaultCurrency} is set as the household currency. Add adults and children here as the family grows.`
                : 'Loading your household workspace.'}
            </p>
          </div>

          {!household ? (
            <div className="empty-state">Loading household...</div>
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
                  <input value={memberName} onChange={(event) => setMemberName(event.target.value)} required />
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
