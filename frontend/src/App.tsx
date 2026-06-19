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

type ExpenseCategory = {
  id: string
  name: string
  slug: string
  color: string
}

type ExpenseItem = {
  id: string
  description: string
  amount: number
  currency: string
  spentOn: string
  category: ExpenseCategory
  paidByMemberId: string | null
}

type RecurringBill = {
  id: string
  name: string
  amount: number
  currency: string
  dueDay: number
  category: ExpenseCategory
  paidByMemberId: string | null
}

type ExpenseOverview = {
  currency: string
  monthTotal: number
  recurringMonthlyTotal: number
  categories: ExpenseCategory[]
  latestExpenses: ExpenseItem[]
  recurringBills: RecurringBill[]
  byCategory: Array<{ name: string; color: string; amount: number }>
}

type AppPage = 'dashboard' | 'household' | 'expenses' | 'health' | 'documents'

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

const today = new Date().toISOString().slice(0, 10)

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
  const [expenseOverview, setExpenseOverview] = useState<ExpenseOverview | null>(null)
  const [householdName, setHouseholdName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [displayName, setDisplayName] = useState('')
  const [memberName, setMemberName] = useState('')
  const [memberType, setMemberType] = useState<'adult' | 'child'>('adult')
  const [setupState, setSetupState] = useState<'idle' | 'saving' | 'error'>('idle')
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login')
  const [activePage, setActivePage] = useState<AppPage>('dashboard')
  const [expenseDescription, setExpenseDescription] = useState('')
  const [expenseAmount, setExpenseAmount] = useState('')
  const [expenseCategoryId, setExpenseCategoryId] = useState('')
  const [expensePaidByMemberId, setExpensePaidByMemberId] = useState('')
  const [expenseSpentOn, setExpenseSpentOn] = useState(today)
  const [billName, setBillName] = useState('')
  const [billAmount, setBillAmount] = useState('')
  const [billCategoryId, setBillCategoryId] = useState('')
  const [billPaidByMemberId, setBillPaidByMemberId] = useState('')
  const [billDueDay, setBillDueDay] = useState('10')

  useEffect(() => {
    const readPageFromHash = () => {
      const page = window.location.hash.replace('#', '') as AppPage

      if (['dashboard', 'household', 'expenses', 'health', 'documents'].includes(page)) {
        setActivePage(page)
      }
    }

    readPageFromHash()
    window.addEventListener('hashchange', readPageFromHash)

    return () => window.removeEventListener('hashchange', readPageFromHash)
  }, [])

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
        return Promise.all([
          apiJson<Household>(`/api/households/${user.householdId}`).then(setHousehold),
          loadExpenseOverview(user.householdId),
        ])
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
      await loadExpenseOverview(user.householdId)
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
    setExpenseOverview(null)
    window.localStorage.removeItem(householdStorageKey)
  }

  const loadExpenseOverview = async (householdId: string) => {
    const overview = await apiJson<ExpenseOverview>(`/api/households/${householdId}/expenses/overview`)
    setExpenseOverview(overview)

    if (!expenseCategoryId && overview.categories[0]) {
      setExpenseCategoryId(overview.categories[0].id)
    }

    if (!billCategoryId && overview.categories[0]) {
      setBillCategoryId(overview.categories[0].id)
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

  const addExpense = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !expenseCategoryId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses`, {
        method: 'POST',
        body: JSON.stringify({
          categoryId: expenseCategoryId,
          description: expenseDescription,
          amount: Number(expenseAmount),
          spentOn: expenseSpentOn,
          paidByMemberId: expensePaidByMemberId || null,
        }),
      })
      await loadExpenseOverview(household.id)
      setExpenseDescription('')
      setExpenseAmount('')
      setExpenseSpentOn(today)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const addRecurringBill = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !billCategoryId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/recurring-bills`, {
        method: 'POST',
        body: JSON.stringify({
          categoryId: billCategoryId,
          name: billName,
          amount: Number(billAmount),
          dueDay: Number(billDueDay),
          paidByMemberId: billPaidByMemberId || null,
        }),
      })
      await loadExpenseOverview(household.id)
      setBillName('')
      setBillAmount('')
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const memberNameById = (memberId: string | null) => {
    if (!memberId || !household) {
      return 'Household'
    }

    return household.members.find((member) => member.id === memberId)?.displayName ?? 'Household'
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
      value: `${(expenseOverview?.monthTotal ?? dashboard.summary.monthlySpend).toLocaleString('pl-PL')} PLN`,
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

  const navItems: Array<{ page: AppPage; label: string }> = [
    { page: 'dashboard', label: 'Dashboard' },
    { page: 'household', label: 'Household' },
    { page: 'expenses', label: 'Expenses' },
    { page: 'health', label: 'Health' },
    { page: 'documents', label: 'Documents' },
  ]

  const pageTitles: Record<AppPage, { eyebrow: string; title: string; copy: string }> = {
    dashboard: {
      eyebrow: 'Today',
      title: `Welcome back, ${currentUser?.displayName ?? 'home'}.`,
      copy: 'A focused overview of your home, money, health, and documents.',
    },
    household: {
      eyebrow: 'Household',
      title: household?.name ?? 'Loading household...',
      copy: household ? `${household.defaultCurrency} is set as the household currency.` : 'Loading your family workspace.',
    },
    expenses: {
      eyebrow: 'Expenses',
      title: 'Track household spending in PLN.',
      copy: 'Add daily costs, monthly bills, and see what is happening this month.',
    },
    health: {
      eyebrow: 'Health',
      title: 'Health records will live here.',
      copy: 'Blood tests, markers, appointments, symptoms, and documents will become a dedicated health workspace.',
    },
    documents: {
      eyebrow: 'Documents',
      title: 'Important files in one place.',
      copy: 'Contracts, invoices, warranties, manuals, and lab PDFs will be organized here.',
    },
  }

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
          {navItems.map((item) => (
            <a
              href={`#${item.page}`}
              className={activePage === item.page ? 'active' : ''}
              key={item.page}
              onClick={() => setActivePage(item.page)}
            >
              {item.label}
            </a>
          ))}
        </nav>
      </aside>

      <section className="workspace">
        <header className="page-hero">
          <div className="page-hero-copy">
            <p className="eyebrow">{pageTitles[activePage].eyebrow}</p>
            <h1>{pageTitles[activePage].title}</h1>
            <p>{pageTitles[activePage].copy}</p>
          </div>
          <div className="page-actions">
            <div className={`api-pill ${apiState}`}>
              <span></span>
              API {apiState}
            </div>
            <button className="logout-button" type="button" onClick={logout}>
              Log out
            </button>
          </div>
        </header>

        {activePage === 'dashboard' && (
          <section className="page-stack">
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
        )}

        {activePage === 'household' && (
          <section className="setup-panel page-card">
            <div>
              <p className="eyebrow">Family</p>
              <h2>Members and household settings.</h2>
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
        )}

        {activePage === 'expenses' && (
          <section className="expenses-panel page-card">
            <div className="section-heading">
              <div>
                <p className="eyebrow">Money</p>
                <h2>Expenses, bills, and category totals.</h2>
              </div>
              <div className="expense-total">
                <span>This month</span>
                <strong>{(expenseOverview?.monthTotal ?? 0).toLocaleString('pl-PL')} PLN</strong>
              </div>
            </div>

            <div className="expense-summary-grid">
              <article>
                <span>Recurring monthly</span>
                <strong>{(expenseOverview?.recurringMonthlyTotal ?? 0).toLocaleString('pl-PL')} PLN</strong>
              </article>
              <article>
                <span>Categories</span>
                <strong>{expenseOverview?.categories.length ?? 0}</strong>
              </article>
              <article>
                <span>Latest entries</span>
                <strong>{expenseOverview?.latestExpenses.length ?? 0}</strong>
              </article>
            </div>

            <div className="expenses-workspace">
              <form className="setup-form expense-form" onSubmit={addExpense}>
                <h3>Add expense</h3>
                <label>
                  Description
                  <input value={expenseDescription} onChange={(event) => setExpenseDescription(event.target.value)} required />
                </label>
                <label>
                  Amount PLN
                  <input
                    type="number"
                    min="0.01"
                    step="0.01"
                    value={expenseAmount}
                    onChange={(event) => setExpenseAmount(event.target.value)}
                    required
                  />
                </label>
                <label>
                  Category
                  <select value={expenseCategoryId} onChange={(event) => setExpenseCategoryId(event.target.value)} required>
                    {(expenseOverview?.categories ?? []).map((category) => (
                      <option value={category.id} key={category.id}>{category.name}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Paid by
                  <select value={expensePaidByMemberId} onChange={(event) => setExpensePaidByMemberId(event.target.value)}>
                    <option value="">Household</option>
                    {(household?.members ?? []).map((member) => (
                      <option value={member.id} key={member.id}>{member.displayName}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Date
                  <input type="date" value={expenseSpentOn} onChange={(event) => setExpenseSpentOn(event.target.value)} required />
                </label>
                <button type="submit" disabled={setupState === 'saving'}>
                  Add expense
                </button>
              </form>

              <form className="setup-form expense-form" onSubmit={addRecurringBill}>
                <h3>Add recurring bill</h3>
                <label>
                  Name
                  <input value={billName} onChange={(event) => setBillName(event.target.value)} required />
                </label>
                <label>
                  Amount PLN
                  <input
                    type="number"
                    min="0.01"
                    step="0.01"
                    value={billAmount}
                    onChange={(event) => setBillAmount(event.target.value)}
                    required
                  />
                </label>
                <label>
                  Category
                  <select value={billCategoryId} onChange={(event) => setBillCategoryId(event.target.value)} required>
                    {(expenseOverview?.categories ?? []).map((category) => (
                      <option value={category.id} key={category.id}>{category.name}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Paid by
                  <select value={billPaidByMemberId} onChange={(event) => setBillPaidByMemberId(event.target.value)}>
                    <option value="">Household</option>
                    {(household?.members ?? []).map((member) => (
                      <option value={member.id} key={member.id}>{member.displayName}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Due day
                  <input
                    type="number"
                    min="1"
                    max="31"
                    value={billDueDay}
                    onChange={(event) => setBillDueDay(event.target.value)}
                    required
                  />
                </label>
                <button type="submit" disabled={setupState === 'saving'}>
                  Add bill
                </button>
              </form>
            </div>

            <div className="expense-lists">
              <section>
                <h3>Latest expenses</h3>
                <div className="money-list">
                  {(expenseOverview?.latestExpenses ?? []).length > 0 ? (
                    expenseOverview?.latestExpenses.map((expense) => (
                      <article className="money-item" key={expense.id}>
                        <span style={{ background: expense.category.color }}></span>
                        <div>
                          <strong>{expense.description}</strong>
                          <small>{expense.category.name} · {memberNameById(expense.paidByMemberId)} · {expense.spentOn}</small>
                        </div>
                        <b>{expense.amount.toLocaleString('pl-PL')} PLN</b>
                      </article>
                    ))
                  ) : (
                    <p className="empty-state">No expenses yet.</p>
                  )}
                </div>
              </section>

              <section>
                <h3>Recurring bills</h3>
                <div className="money-list">
                  {(expenseOverview?.recurringBills ?? []).length > 0 ? (
                    expenseOverview?.recurringBills.map((bill) => (
                      <article className="money-item" key={bill.id}>
                        <span style={{ background: bill.category.color }}></span>
                        <div>
                          <strong>{bill.name}</strong>
                          <small>{bill.category.name} · due day {bill.dueDay} · {memberNameById(bill.paidByMemberId)}</small>
                        </div>
                        <b>{bill.amount.toLocaleString('pl-PL')} PLN</b>
                      </article>
                    ))
                  ) : (
                    <p className="empty-state">No recurring bills yet.</p>
                  )}
                </div>
              </section>
            </div>

            <section className="category-strip" aria-label="Spending by category">
              {(expenseOverview?.byCategory ?? []).length > 0 ? (
                expenseOverview?.byCategory.map((category) => (
                  <article key={category.name}>
                    <span style={{ background: category.color }}></span>
                    <strong>{category.name}</strong>
                    <small>{category.amount.toLocaleString('pl-PL')} PLN</small>
                  </article>
                ))
              ) : (
                <p className="empty-state">Category totals will appear after you add expenses this month.</p>
              )}
            </section>

            {setupState === 'error' && <p className="form-error">Could not save expenses data.</p>}
          </section>
        )}

        {activePage === 'health' && (
          <section className="placeholder-grid">
            {[
              ['Blood tests', 'Markers, reference ranges, and trends over time.'],
              ['Appointments', 'Visits, reminders, and notes for every family member.'],
              ['Health files', 'Lab PDFs, prescriptions, and medical documents.'],
            ].map(([title, copy]) => (
              <article className="placeholder-card" key={title}>
                <span></span>
                <h2>{title}</h2>
                <p>{copy}</p>
              </article>
            ))}
          </section>
        )}

        {activePage === 'documents' && (
          <section className="placeholder-grid">
            {[
              ['Contracts', 'Internet, phone, mortgage, insurance, and service agreements.'],
              ['Invoices', 'Recurring bills, purchase proofs, and household costs.'],
              ['Archive', 'Manuals, warranties, certificates, and scanned documents.'],
            ].map(([title, copy]) => (
              <article className="placeholder-card" key={title}>
                <span></span>
                <h2>{title}</h2>
                <p>{copy}</p>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

export default App
