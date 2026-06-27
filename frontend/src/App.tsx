import { type FormEvent, type SetStateAction, useEffect, useState } from 'react'
import './App.css'

type Dashboard = {
  app: string
  status: string
  summary: {
    homeTasksDue: number
    monthlySpend: number
    projectedBalance: number
    financeReviewCount: number
    healthMarkersTracked: number
    healthOutOfRange: number
    documentsStored: number
  }
  attention: Array<{
    id: string
    area: 'expenses' | 'health' | 'home'
    severity: 'critical' | 'warning' | 'info'
    title: string
    detail: string
    actionLabel: string
    targetPage: AppPage
    targetSection: ExpenseSection | null
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
  reviewStatus: 'needs_review' | 'reviewed'
  reviewReason: string | null
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

type IncomeSource = {
  id: string
  memberId: string | null
  name: string
  amount: number
  currency: string
  active: boolean
}

type IncomeEntry = {
  id: string
  sourceId: string | null
  memberId: string | null
  description: string
  amount: number
  currency: string
  receivedOn: string
  incomeKind: 'salary' | 'transfer' | 'refund' | 'other'
  reviewStatus: 'needs_review' | 'reviewed'
  reviewReason: string | null
}

type BillChecklistItem = {
  bill: RecurringBill
  status: 'planned' | 'paid' | 'skipped'
  amount: number
  paidOn: string | null
}

type ExpenseOverview = {
  currency: string
  monthTotal: number
  recurringMonthlyTotal: number
  categories: ExpenseCategory[]
  latestExpenses: ExpenseItem[]
  recurringBills: RecurringBill[]
  byCategory: Array<{ name: string; color: string; amount: number }>
  expectedIncome: number
  actualIncome: number
  spentTotal: number
  recurringPlannedTotal: number
  paidBillsTotal: number
  remainingMonthlyMoney: number
  projectedMonthEndBalance: number
  incomeSources: IncomeSource[]
  incomeEntries: IncomeEntry[]
  budgetUsage: Array<{
    category: ExpenseCategory
    budget: number
    spent: number
    remaining: number
    percent: number
    overBudget: boolean
  }>
  billChecklist: {
    upcoming: BillChecklistItem[]
    paid: BillChecklistItem[]
    overdue: BillChecklistItem[]
    skipped: BillChecklistItem[]
  }
  topCategories: Array<{ name: string; color: string; amount: number }>
  memberTotals: Array<{ memberId: string | null; amount: number }>
  dailySpending: Array<{ date: string; expense: number; income: number }>
  monthlyTrend: Array<{ month: string; expense: number; income: number; balance: number }>
  reviewRules: Array<{
    id: string
    targetType: 'expense' | 'income'
    matchText: string
    categoryId: string | null
    incomeKind: IncomeEntry['incomeKind'] | null
    lastAppliedAt: string | null
  }>
  review: {
    needsReviewCount: number
    expenseNeedsReviewCount: number
    incomeNeedsReviewCount: number
    excludedIncomeTotal: number
    lastAppliedBatch: {
      id: string
      targetType: 'expense' | 'income'
      matchText: string
      appliedCount: number
      createdAt: string
    } | null
    expenseCandidates: ExpenseItem[]
    incomeCandidates: IncomeEntry[]
  }
  activeFilters: {
    month: string
    categoryId: string | null
    paidByMemberId: string | null
  }
}

type BloodTestMarker = {
  id: string
  bloodTestId: string
  name: string
  value: number
  unit: string
  referenceMin: number | null
  referenceMax: number | null
  status: 'normal' | 'low' | 'high' | 'unknown'
  notes: string | null
  testedAt: string
  memberId: string
}

type BloodTest = {
  id: string
  memberId: string
  testedAt: string
  labName: string | null
  notes: string | null
  sourceDocumentId: string | null
  markers: BloodTestMarker[]
  createdAt: string
}

type HealthOverview = {
  memberId: string | null
  latestBloodTests: BloodTest[]
  outOfRangeMarkers: BloodTestMarker[]
  markerNames: string[]
  markerCatalog: MarkerCatalogItem[]
}

type MarkerCatalogItem = {
  name: string
  aliases: string[]
  unit: string
  referenceMin: number | null
  referenceMax: number | null
  category: string
}

type HealthDocument = {
  id: string
  memberId: string | null
  documentType: string
  originalName: string
  mimeType: string
  size: number
  uploadedAt: string
  downloadUrl: string
}

type HomeMaintenanceTask = {
  id: string
  householdId: string
  title: string
  area: string
  nextDueAt: string
  recurrenceType: 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly'
  assignedMemberId: string | null
  priority: 'low' | 'normal' | 'high'
  notes: string | null
  status: 'active' | 'completed'
  createdAt: string
  completedAt: string | null
}

type DocumentExtraction = {
  documentId: string
  status: 'extracted' | 'empty' | 'failed' | 'missing_file' | 'tool_missing' | 'unsupported'
  text: string
  message: string | null
  suggestedTestedAt: string | null
  markers: Array<{
    markerName: string
    value: number
    unit: string
    referenceMin: number | null
    referenceMax: number | null
    status: 'normal' | 'low' | 'high' | 'unknown'
    notes: string | null
  }>
}

type MarkerFormRow = {
  id: string
  selected: boolean
  markerName: string
  value: string
  unit: string
  referenceMin: string
  referenceMax: string
  status: 'normal' | 'low' | 'high' | 'unknown'
  notes: string
}

type AppPage = 'dashboard' | 'household' | 'home' | 'expenses' | 'health' | 'documents'
type ExpenseSection = 'overview' | 'monthly-review' | 'analytics' | 'transactions' | 'import-review' | 'budgets' | 'bills'

const fallbackDashboard: Dashboard = {
  app: 'Home OS',
  status: 'offline',
  summary: {
    homeTasksDue: 0,
    monthlySpend: 0,
    projectedBalance: 0,
    financeReviewCount: 0,
    healthMarkersTracked: 0,
    healthOutOfRange: 0,
    documentsStored: 0,
  },
  attention: [],
}

const householdStorageKey = 'home-os.household-id'

const today = new Date().toISOString().slice(0, 10)
const currentMonth = today.slice(0, 7)
const createMarkerRow = (): MarkerFormRow => ({
  id: crypto.randomUUID(),
  selected: true,
  markerName: '',
  value: '',
  unit: '',
  referenceMin: '',
  referenceMax: '',
  status: 'unknown',
  notes: '',
})

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

async function apiNoContent(url: string, options?: RequestInit): Promise<void> {
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
}

async function apiFormData<T>(url: string, body: FormData): Promise<T> {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    body,
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
  const [healthOverview, setHealthOverview] = useState<HealthOverview | null>(null)
  const [healthDocuments, setHealthDocuments] = useState<HealthDocument[]>([])
  const [homeTasks, setHomeTasks] = useState<HomeMaintenanceTask[]>([])
  const [householdName, setHouseholdName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [displayName, setDisplayName] = useState('')
  const [memberName, setMemberName] = useState('')
  const [memberType, setMemberType] = useState<'adult' | 'child'>('adult')
  const [setupState, setSetupState] = useState<'idle' | 'saving' | 'error'>('idle')
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login')
  const [activePage, setActivePage] = useState<AppPage>('dashboard')
  const [expenseSection, setExpenseSection] = useState<ExpenseSection>('overview')
  const [openHomeTaskCreator, setOpenHomeTaskCreator] = useState(false)
  const [homeTaskTitle, setHomeTaskTitle] = useState('')
  const [homeTaskArea, setHomeTaskArea] = useState('')
  const [homeTaskNextDueAt, setHomeTaskNextDueAt] = useState(today)
  const [homeTaskRecurrenceType, setHomeTaskRecurrenceType] = useState<HomeMaintenanceTask['recurrenceType']>('none')
  const [homeTaskAssignedMemberId, setHomeTaskAssignedMemberId] = useState('')
  const [homeTaskPriority, setHomeTaskPriority] = useState<HomeMaintenanceTask['priority']>('normal')
  const [homeTaskNotes, setHomeTaskNotes] = useState('')
  const [editingHomeTaskId, setEditingHomeTaskId] = useState<string | null>(null)
  const [editHomeTaskTitle, setEditHomeTaskTitle] = useState('')
  const [editHomeTaskArea, setEditHomeTaskArea] = useState('')
  const [editHomeTaskNextDueAt, setEditHomeTaskNextDueAt] = useState(today)
  const [editHomeTaskRecurrenceType, setEditHomeTaskRecurrenceType] = useState<HomeMaintenanceTask['recurrenceType']>('none')
  const [editHomeTaskAssignedMemberId, setEditHomeTaskAssignedMemberId] = useState('')
  const [editHomeTaskPriority, setEditHomeTaskPriority] = useState<HomeMaintenanceTask['priority']>('normal')
  const [editHomeTaskNotes, setEditHomeTaskNotes] = useState('')
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
  const [openExpenseCreator, setOpenExpenseCreator] = useState<'expense' | 'bill' | null>(null)
  const [expenseFilterMonth, setExpenseFilterMonth] = useState(currentMonth)
  const [expenseFilterCategoryId, setExpenseFilterCategoryId] = useState('')
  const [expenseFilterPaidByMemberId, setExpenseFilterPaidByMemberId] = useState('')
  const [editingExpenseId, setEditingExpenseId] = useState<string | null>(null)
  const [editExpenseDescription, setEditExpenseDescription] = useState('')
  const [editExpenseAmount, setEditExpenseAmount] = useState('')
  const [editExpenseCategoryId, setEditExpenseCategoryId] = useState('')
  const [editExpensePaidByMemberId, setEditExpensePaidByMemberId] = useState('')
  const [editExpenseSpentOn, setEditExpenseSpentOn] = useState(today)
  const [editingBillId, setEditingBillId] = useState<string | null>(null)
  const [editBillName, setEditBillName] = useState('')
  const [editBillAmount, setEditBillAmount] = useState('')
  const [editBillCategoryId, setEditBillCategoryId] = useState('')
  const [editBillPaidByMemberId, setEditBillPaidByMemberId] = useState('')
  const [editBillDueDay, setEditBillDueDay] = useState('10')
  const [openMoneyPanel, setOpenMoneyPanel] = useState<'income-source' | 'income-entry' | 'budgets' | null>(null)
  const [incomeSourceName, setIncomeSourceName] = useState('')
  const [incomeSourceAmount, setIncomeSourceAmount] = useState('')
  const [incomeSourceMemberId, setIncomeSourceMemberId] = useState('')
  const [incomeEntryDescription, setIncomeEntryDescription] = useState('')
  const [incomeEntryAmount, setIncomeEntryAmount] = useState('')
  const [incomeEntryMemberId, setIncomeEntryMemberId] = useState('')
  const [incomeEntrySourceId, setIncomeEntrySourceId] = useState('')
  const [incomeEntryReceivedOn, setIncomeEntryReceivedOn] = useState(today)
  const [budgetDrafts, setBudgetDrafts] = useState<Record<string, string>>({})
  const [healthMemberFilterId, setHealthMemberFilterId] = useState('')
  const [openBloodTestCreator, setOpenBloodTestCreator] = useState(false)
  const [bloodTestMemberId, setBloodTestMemberId] = useState('')
  const [bloodTestTestedAt, setBloodTestTestedAt] = useState(today)
  const [bloodTestLabName, setBloodTestLabName] = useState('')
  const [bloodTestNotes, setBloodTestNotes] = useState('')
  const [markerRows, setMarkerRows] = useState<MarkerFormRow[]>([createMarkerRow()])
  const [editingBloodTestId, setEditingBloodTestId] = useState<string | null>(null)
  const [editBloodTestMemberId, setEditBloodTestMemberId] = useState('')
  const [editBloodTestTestedAt, setEditBloodTestTestedAt] = useState(today)
  const [editBloodTestLabName, setEditBloodTestLabName] = useState('')
  const [editBloodTestNotes, setEditBloodTestNotes] = useState('')
  const [editMarkerRows, setEditMarkerRows] = useState<MarkerFormRow[]>([createMarkerRow()])
  const [selectedMarkerName, setSelectedMarkerName] = useState('')
  const [markerHistory, setMarkerHistory] = useState<BloodTestMarker[]>([])
  const [documentMemberId, setDocumentMemberId] = useState('')
  const [documentFile, setDocumentFile] = useState<File | null>(null)
  const [importDocument, setImportDocument] = useState<HealthDocument | null>(null)
  const [importMemberId, setImportMemberId] = useState('')
  const [importTestedAt, setImportTestedAt] = useState(today)
  const [importSuggestedTestedAt, setImportSuggestedTestedAt] = useState('')
  const [importLabName, setImportLabName] = useState('')
  const [importNotes, setImportNotes] = useState('')
  const [importMarkerRows, setImportMarkerRows] = useState<MarkerFormRow[]>([createMarkerRow()])
  const [extractedText, setExtractedText] = useState('')
  const [extractionStatus, setExtractionStatus] = useState<DocumentExtraction['status'] | ''>('')
  const [extractionMessage, setExtractionMessage] = useState('')

  useEffect(() => {
    const readPageFromHash = () => {
      const page = window.location.hash.replace('#', '') as AppPage

      if (['dashboard', 'household', 'home', 'expenses', 'health', 'documents'].includes(page)) {
        setActivePage(page)
      }
    }

    readPageFromHash()
    window.addEventListener('hashchange', readPageFromHash)

    return () => window.removeEventListener('hashchange', readPageFromHash)
  }, [])

  const loadDashboard = async () => {
    setDashboard(await apiJson<Dashboard>('/api/dashboard'))
    setApiState('online')
  }

  useEffect(() => {
    apiJson<{ user: CurrentUser | null }>('/api/auth/me')
      .then(({ user }) => {
        setApiState('online')
        setCurrentUser(user)

        if (!user) {
          return
        }

        window.localStorage.setItem(householdStorageKey, user.householdId)
        return Promise.all([
          apiJson<Household>(`/api/households/${user.householdId}`).then(setHousehold),
          loadDashboard(),
          loadHomeTasks(user.householdId),
          loadExpenseOverview(user.householdId),
          loadHealthOverview(user.householdId),
          loadHealthDocuments(user.householdId),
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
      await loadDashboard()
      await loadHomeTasks(user.householdId)
      await loadExpenseOverview(user.householdId)
      await loadHealthOverview(user.householdId)
      await loadHealthDocuments(user.householdId)
      window.localStorage.setItem(householdStorageKey, user.householdId)
      setCurrentUser(user)
      setHousehold(nextHousehold)
      setBloodTestMemberId(user.linkedMemberId ?? nextHousehold.members[0]?.id ?? '')
      setDocumentMemberId(user.linkedMemberId ?? nextHousehold.members[0]?.id ?? '')
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
    setHealthOverview(null)
    setHealthDocuments([])
    setHomeTasks([])
    setMarkerHistory([])
    setDashboard(fallbackDashboard)
    window.localStorage.removeItem(householdStorageKey)
  }

  const loadExpenseOverview = async (householdId: string) => {
    const params = new URLSearchParams()
    params.set('month', expenseFilterMonth)

    if (expenseFilterCategoryId) {
      params.set('categoryId', expenseFilterCategoryId)
    }

    if (expenseFilterPaidByMemberId) {
      params.set('paidByMemberId', expenseFilterPaidByMemberId)
    }

    const overview = await apiJson<ExpenseOverview>(`/api/households/${householdId}/expenses/overview?${params.toString()}`)
    setExpenseOverview(overview)
    setBudgetDrafts(Object.fromEntries(overview.budgetUsage.map((row) => [row.category.id, row.budget ? String(row.budget) : ''])))

    if (!expenseCategoryId && overview.categories[0]) {
      setExpenseCategoryId(overview.categories[0].id)
    }

    if (!billCategoryId && overview.categories[0]) {
      setBillCategoryId(overview.categories[0].id)
    }
  }

  const refreshExpenseAndDashboard = async (householdId: string) => {
    await loadExpenseOverview(householdId)
    await loadDashboard()
  }

  const loadHomeTasks = async (householdId: string) => {
    const response = await apiJson<{ tasks: HomeMaintenanceTask[] }>(`/api/households/${householdId}/home/maintenance-tasks`)
    setHomeTasks(response.tasks)
  }

  const refreshHomeAndDashboard = async (householdId: string) => {
    await loadHomeTasks(householdId)
    await loadDashboard()
  }

  useEffect(() => {
    if (currentUser) {
      loadExpenseOverview(currentUser.householdId).catch(() => setSetupState('error'))
    }
  }, [expenseFilterMonth, expenseFilterCategoryId, expenseFilterPaidByMemberId])

  const loadHealthOverview = async (householdId: string) => {
    const params = new URLSearchParams()

    if (healthMemberFilterId) {
      params.set('memberId', healthMemberFilterId)
    }

    const suffix = params.toString() ? `?${params.toString()}` : ''
    const overview = await apiJson<HealthOverview>(`/api/households/${householdId}/health/overview${suffix}`)
    setHealthOverview(overview)

    if (!selectedMarkerName && overview.markerNames[0]) {
      setSelectedMarkerName(overview.markerNames[0])
    }
  }

  const loadHealthDocuments = async (householdId: string) => {
    const params = new URLSearchParams()

    if (healthMemberFilterId) {
      params.set('memberId', healthMemberFilterId)
    }

    const suffix = params.toString() ? `?${params.toString()}` : ''
    setHealthDocuments(await apiJson<HealthDocument[]>(`/api/households/${householdId}/health/documents${suffix}`))
  }

  const loadMarkerHistory = async (householdId: string, markerName: string) => {
    if (!markerName) {
      setMarkerHistory([])
      return
    }

    const params = new URLSearchParams()

    if (healthMemberFilterId) {
      params.set('memberId', healthMemberFilterId)
    }

    const suffix = params.toString() ? `?${params.toString()}` : ''
    const history = await apiJson<BloodTestMarker[]>(
      `/api/households/${householdId}/health/markers/${encodeURIComponent(markerName)}/history${suffix}`,
    )
    setMarkerHistory(history)
  }

  useEffect(() => {
    if (household && !bloodTestMemberId) {
      setBloodTestMemberId(currentUser?.linkedMemberId ?? household.members[0]?.id ?? '')
    }
    if (household && !documentMemberId) {
      setDocumentMemberId(currentUser?.linkedMemberId ?? household.members[0]?.id ?? '')
    }
  }, [household, currentUser, bloodTestMemberId, documentMemberId])

  useEffect(() => {
    if (currentUser) {
      loadHealthOverview(currentUser.householdId).catch(() => setSetupState('error'))
      loadHealthDocuments(currentUser.householdId).catch(() => setSetupState('error'))
    }
  }, [healthMemberFilterId])

  useEffect(() => {
    if (currentUser && selectedMarkerName) {
      loadMarkerHistory(currentUser.householdId, selectedMarkerName).catch(() => setSetupState('error'))
    }
  }, [selectedMarkerName, healthMemberFilterId])

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

  const addHomeTask = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/home/maintenance-tasks`, {
        method: 'POST',
        body: JSON.stringify({
          title: homeTaskTitle,
          area: homeTaskArea,
          nextDueAt: homeTaskNextDueAt,
          recurrenceType: homeTaskRecurrenceType,
          assignedMemberId: homeTaskAssignedMemberId || null,
          priority: homeTaskPriority,
          notes: homeTaskNotes || null,
        }),
      })
      await refreshHomeAndDashboard(household.id)
      setHomeTaskTitle('')
      setHomeTaskArea('')
      setHomeTaskNextDueAt(today)
      setHomeTaskRecurrenceType('none')
      setHomeTaskAssignedMemberId('')
      setHomeTaskPriority('normal')
      setHomeTaskNotes('')
      setOpenHomeTaskCreator(false)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const startEditHomeTask = (task: HomeMaintenanceTask) => {
    setEditingHomeTaskId(task.id)
    setEditHomeTaskTitle(task.title)
    setEditHomeTaskArea(task.area)
    setEditHomeTaskNextDueAt(task.nextDueAt)
    setEditHomeTaskRecurrenceType(task.recurrenceType)
    setEditHomeTaskAssignedMemberId(task.assignedMemberId ?? '')
    setEditHomeTaskPriority(task.priority)
    setEditHomeTaskNotes(task.notes ?? '')
  }

  const updateHomeTask = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !editingHomeTaskId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ status: string }>(`/api/households/${household.id}/home/maintenance-tasks/${editingHomeTaskId}`, {
        method: 'PATCH',
        body: JSON.stringify({
          title: editHomeTaskTitle,
          area: editHomeTaskArea,
          nextDueAt: editHomeTaskNextDueAt,
          recurrenceType: editHomeTaskRecurrenceType,
          assignedMemberId: editHomeTaskAssignedMemberId || null,
          priority: editHomeTaskPriority,
          notes: editHomeTaskNotes || null,
        }),
      })
      await refreshHomeAndDashboard(household.id)
      setEditingHomeTaskId(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteHomeTask = async (taskId: string) => {
    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiNoContent(`/api/households/${household.id}/home/maintenance-tasks/${taskId}`, { method: 'DELETE' })
      await refreshHomeAndDashboard(household.id)
      setEditingHomeTaskId((current) => (current === taskId ? null : current))
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const completeHomeTask = async (taskId: string) => {
    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ status: string }>(`/api/households/${household.id}/home/maintenance-tasks/${taskId}/complete`, {
        method: 'POST',
        body: JSON.stringify({}),
      })
      await refreshHomeAndDashboard(household.id)
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
      await refreshExpenseAndDashboard(household.id)
      setExpenseDescription('')
      setExpenseAmount('')
      setExpenseSpentOn(today)
      setOpenExpenseCreator(null)
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
      await refreshExpenseAndDashboard(household.id)
      setBillName('')
      setBillAmount('')
      setOpenExpenseCreator(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const startEditExpense = (expense: ExpenseItem) => {
    setEditingExpenseId(expense.id)
    setEditExpenseDescription(expense.description)
    setEditExpenseAmount(String(expense.amount))
    setEditExpenseCategoryId(expense.category.id)
    setEditExpensePaidByMemberId(expense.paidByMemberId ?? '')
    setEditExpenseSpentOn(expense.spentOn)
  }

  const updateExpense = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !editingExpenseId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/${editingExpenseId}`, {
        method: 'PATCH',
        body: JSON.stringify({
          categoryId: editExpenseCategoryId,
          description: editExpenseDescription,
          amount: Number(editExpenseAmount),
          spentOn: editExpenseSpentOn,
          paidByMemberId: editExpensePaidByMemberId || null,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setEditingExpenseId(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteExpense = async (expenseId: string) => {
    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiNoContent(`/api/households/${household.id}/expenses/${expenseId}`, { method: 'DELETE' })
      await refreshExpenseAndDashboard(household.id)
      setEditingExpenseId((current) => (current === expenseId ? null : current))
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const startEditBill = (bill: RecurringBill) => {
    setEditingBillId(bill.id)
    setEditBillName(bill.name)
    setEditBillAmount(String(bill.amount))
    setEditBillCategoryId(bill.category.id)
    setEditBillPaidByMemberId(bill.paidByMemberId ?? '')
    setEditBillDueDay(String(bill.dueDay))
  }

  const updateRecurringBill = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !editingBillId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/recurring-bills/${editingBillId}`, {
        method: 'PATCH',
        body: JSON.stringify({
          categoryId: editBillCategoryId,
          name: editBillName,
          amount: Number(editBillAmount),
          dueDay: Number(editBillDueDay),
          paidByMemberId: editBillPaidByMemberId || null,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setEditingBillId(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteRecurringBill = async (billId: string) => {
    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiNoContent(`/api/households/${household.id}/expenses/recurring-bills/${billId}`, { method: 'DELETE' })
      await refreshExpenseAndDashboard(household.id)
      setEditingBillId((current) => (current === billId ? null : current))
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const addIncomeSource = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/income-sources`, {
        method: 'POST',
        body: JSON.stringify({
          name: incomeSourceName,
          amount: Number(incomeSourceAmount),
          memberId: incomeSourceMemberId || null,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setIncomeSourceName('')
      setIncomeSourceAmount('')
      setOpenMoneyPanel(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteIncomeSource = async (sourceId: string) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiNoContent(`/api/households/${household.id}/expenses/income-sources/${sourceId}`, { method: 'DELETE' })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const addIncomeEntry = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/income-entries`, {
        method: 'POST',
        body: JSON.stringify({
          sourceId: incomeEntrySourceId || null,
          memberId: incomeEntryMemberId || null,
          description: incomeEntryDescription,
          amount: Number(incomeEntryAmount),
          receivedOn: incomeEntryReceivedOn,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setIncomeEntryDescription('')
      setIncomeEntryAmount('')
      setIncomeEntryReceivedOn(today)
      setOpenMoneyPanel(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteIncomeEntry = async (entryId: string) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiNoContent(`/api/households/${household.id}/expenses/income-entries/${entryId}`, { method: 'DELETE' })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const patchExpenseReview = async (expense: ExpenseItem, updates: Partial<Pick<ExpenseItem, 'category' | 'reviewStatus' | 'reviewReason'>>) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/${expense.id}`, {
        method: 'PATCH',
        body: JSON.stringify({
          categoryId: updates.category?.id ?? expense.category.id,
          description: expense.description,
          amount: expense.amount,
          spentOn: expense.spentOn,
          paidByMemberId: expense.paidByMemberId,
          reviewStatus: updates.reviewStatus ?? expense.reviewStatus,
          reviewReason: updates.reviewReason ?? expense.reviewReason,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const changeExpenseCategory = async (expense: ExpenseItem, categoryId: string) => {
    const category = expenseOverview?.categories.find((item) => item.id === categoryId)

    if (!category) {
      return
    }

    await patchExpenseReview(expense, { category, reviewStatus: 'reviewed', reviewReason: null })
  }

  const patchIncomeReview = async (
    entry: IncomeEntry,
    updates: Partial<Pick<IncomeEntry, 'incomeKind' | 'reviewStatus' | 'reviewReason'>>,
  ) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/income-entries/${entry.id}`, {
        method: 'PATCH',
        body: JSON.stringify({
          sourceId: entry.sourceId,
          memberId: entry.memberId,
          description: entry.description,
          amount: entry.amount,
          receivedOn: entry.receivedOn,
          incomeKind: updates.incomeKind ?? entry.incomeKind,
          reviewStatus: updates.reviewStatus ?? entry.reviewStatus,
          reviewReason: updates.reviewReason ?? entry.reviewReason,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const applyFinanceReviewRule = async (
    targetType: 'expense' | 'income',
    matchText: string,
    categoryId: string | null,
    incomeKind: IncomeEntry['incomeKind'] | null,
  ) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string, appliedCount: number }>(`/api/households/${household.id}/expenses/review-rules/apply`, {
        method: 'POST',
        body: JSON.stringify({
          targetType,
          matchText,
          month: expenseFilterMonth,
          categoryId,
          incomeKind,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const undoLastFinanceReviewBatch = async () => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ undoneCount: number }>(`/api/households/${household.id}/expenses/review-batches/undo-last`, {
        method: 'POST',
        body: JSON.stringify({}),
      })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const saveBudgets = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ month: string }>(`/api/households/${household.id}/expenses/budgets/${expenseFilterMonth}`, {
        method: 'PUT',
        body: JSON.stringify({
          budgets: Object.entries(budgetDrafts).map(([categoryId, amount]) => ({ categoryId, amount: amount === '' ? 0 : Number(amount) })),
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setOpenMoneyPanel(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const updateBillPayment = async (billId: string, status: 'planned' | 'paid' | 'skipped', amount?: number) => {
    if (!household) return
    setSetupState('saving')
    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/expenses/recurring-bills/${billId}/payments/${expenseFilterMonth}`, {
        method: 'PATCH',
        body: JSON.stringify({
          status,
          paidOn: status === 'paid' ? today : null,
          amount: amount ?? null,
        }),
      })
      await refreshExpenseAndDashboard(household.id)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const findMarkerCatalogItem = (name: string) => {
    const normalized = name.trim().toLocaleLowerCase('pl-PL')

    return healthOverview?.markerCatalog.find((marker) => (
      marker.name.toLocaleLowerCase('pl-PL') === normalized
      || marker.aliases.some((alias) => alias.toLocaleLowerCase('pl-PL') === normalized)
    )) ?? null
  }

  const applyMarkerCatalog = (row: MarkerFormRow) => {
    const catalogItem = findMarkerCatalogItem(row.markerName)

    if (!catalogItem) {
      return row
    }

    return {
      ...row,
      markerName: catalogItem.name,
      unit: row.unit || catalogItem.unit,
      referenceMin: row.referenceMin || (catalogItem.referenceMin === null ? '' : String(catalogItem.referenceMin)),
      referenceMax: row.referenceMax || (catalogItem.referenceMax === null ? '' : String(catalogItem.referenceMax)),
    }
  }

  const updateRows = (
    setter: (value: SetStateAction<MarkerFormRow[]>) => void,
    rowId: string,
    field: keyof MarkerFormRow,
    value: string | boolean,
  ) => {
    setter((rows) => rows.map((row) => {
      if (row.id !== rowId) {
        return row
      }

      const nextRow = { ...row, [field]: value }

      return field === 'markerName' ? applyMarkerCatalog(nextRow) : nextRow
    }))
  }

  const updateMarkerRow = (rowId: string, field: keyof MarkerFormRow, value: string | boolean) => {
    updateRows(setMarkerRows, rowId, field, value)
  }

  const updateImportMarkerRow = (rowId: string, field: keyof MarkerFormRow, value: string | boolean) => {
    updateRows(setImportMarkerRows, rowId, field, value)
  }

  const updateEditMarkerRow = (rowId: string, field: keyof MarkerFormRow, value: string | boolean) => {
    updateRows(setEditMarkerRows, rowId, field, value)
  }

  const removeMarkerRow = (rowId: string) => {
    setMarkerRows((rows) => (rows.length === 1 ? rows : rows.filter((row) => row.id !== rowId)))
  }

  const removeImportMarkerRow = (rowId: string) => {
    setImportMarkerRows((rows) => (rows.length === 1 ? rows : rows.filter((row) => row.id !== rowId)))
  }

  const removeEditMarkerRow = (rowId: string) => {
    setEditMarkerRows((rows) => (rows.length === 1 ? rows : rows.filter((row) => row.id !== rowId)))
  }

  const markerPayload = (rows: MarkerFormRow[]) => rows
    .filter((row) => row.selected)
    .map((row) => ({
      markerName: row.markerName,
      value: Number(row.value),
      unit: row.unit,
      referenceMin: row.referenceMin ? Number(row.referenceMin) : null,
      referenceMax: row.referenceMax ? Number(row.referenceMax) : null,
      status: row.status,
      notes: row.notes || null,
    }))

  const markerWarnings = (row: MarkerFormRow) => {
    const warnings: string[] = []

    if (!row.unit.trim()) {
      warnings.push('missing unit')
    }

    if (row.referenceMin && row.referenceMax && Number(row.referenceMin) > Number(row.referenceMax)) {
      warnings.push('range reversed')
    }

    if (!findMarkerCatalogItem(row.markerName)) {
      warnings.push('new marker name')
    }

    if (row.status === 'low' || row.status === 'high') {
      warnings.push(row.status)
    }

    return warnings
  }

  const bloodTestToRows = (test: BloodTest): MarkerFormRow[] => test.markers.map((marker) => ({
    id: crypto.randomUUID(),
    selected: true,
    markerName: marker.name,
    value: String(marker.value),
    unit: marker.unit,
    referenceMin: marker.referenceMin === null ? '' : String(marker.referenceMin),
    referenceMax: marker.referenceMax === null ? '' : String(marker.referenceMax),
    status: marker.status,
    notes: marker.notes ?? '',
  }))

  const startEditBloodTest = (test: BloodTest) => {
    setEditingBloodTestId(test.id)
    setEditBloodTestMemberId(test.memberId)
    setEditBloodTestTestedAt(test.testedAt)
    setEditBloodTestLabName(test.labName ?? '')
    setEditBloodTestNotes(test.notes ?? '')
    setEditMarkerRows(bloodTestToRows(test))
  }

  const startDocumentImport = (document: HealthDocument) => {
    setImportDocument(document)
    setImportMemberId(document.memberId ?? currentUser?.linkedMemberId ?? household?.members[0]?.id ?? '')
    setImportTestedAt(today)
    setImportSuggestedTestedAt('')
    setImportLabName('')
    setImportNotes(`Imported from ${document.originalName}`)
    setImportMarkerRows([createMarkerRow()])
    setExtractedText('')
    setExtractionStatus('')
    setExtractionMessage('')
  }

  const addBloodTest = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !bloodTestMemberId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/health/blood-tests`, {
        method: 'POST',
        body: JSON.stringify({
          memberId: bloodTestMemberId,
          testedAt: bloodTestTestedAt,
          labName: bloodTestLabName || null,
          notes: bloodTestNotes || null,
          markers: markerPayload(markerRows),
        }),
      })
      await loadHealthOverview(household.id)
      setBloodTestTestedAt(today)
      setBloodTestLabName('')
      setBloodTestNotes('')
      setMarkerRows([createMarkerRow()])
      setOpenBloodTestCreator(false)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const saveDocumentImport = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !importDocument || !importMemberId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/health/blood-tests`, {
        method: 'POST',
        body: JSON.stringify({
          memberId: importMemberId,
          testedAt: importTestedAt,
          labName: importLabName || null,
          notes: importNotes || null,
          sourceDocumentId: importDocument.id,
          markers: markerPayload(importMarkerRows),
        }),
      })
      await loadHealthOverview(household.id)
      setImportDocument(null)
      setImportSuggestedTestedAt('')
      setImportLabName('')
      setImportNotes('')
      setImportMarkerRows([createMarkerRow()])
      setExtractedText('')
      setExtractionStatus('')
      setExtractionMessage('')
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const extractHealthDocumentText = async (document: HealthDocument) => {
    if (!household) {
      return
    }

    startDocumentImport(document)
    setSetupState('saving')

    try {
      const extraction = await apiJson<DocumentExtraction>(
        `/api/households/${household.id}/health/documents/${document.id}/extract-text`,
        { method: 'POST' },
      )
      setExtractedText(extraction.text)
      setExtractionStatus(extraction.status)
      setExtractionMessage(extraction.message ?? '')
      setImportSuggestedTestedAt(extraction.suggestedTestedAt ?? '')
      if (extraction.suggestedTestedAt) {
        setImportTestedAt(extraction.suggestedTestedAt)
      }
      if (extraction.markers.length > 0) {
        setImportMarkerRows(extraction.markers.map((marker) => ({
          id: crypto.randomUUID(),
          selected: marker.unit !== '',
          markerName: marker.markerName,
          value: String(marker.value),
          unit: marker.unit,
          referenceMin: marker.referenceMin === null ? '' : String(marker.referenceMin),
          referenceMax: marker.referenceMax === null ? '' : String(marker.referenceMax),
          status: marker.status,
          notes: marker.notes ?? '',
        })))
      }
      setSetupState('idle')
    } catch {
      setExtractionStatus('failed')
      setExtractionMessage('Could not extract text from this document.')
      setSetupState('error')
    }
  }

  const updateBloodTest = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !editingBloodTestId || !editBloodTestMemberId) {
      return
    }

    setSetupState('saving')

    try {
      await apiJson<{ id: string }>(`/api/households/${household.id}/health/blood-tests/${editingBloodTestId}`, {
        method: 'PATCH',
        body: JSON.stringify({
          memberId: editBloodTestMemberId,
          testedAt: editBloodTestTestedAt,
          labName: editBloodTestLabName || null,
          notes: editBloodTestNotes || null,
          markers: markerPayload(editMarkerRows),
        }),
      })
      await loadHealthOverview(household.id)
      if (selectedMarkerName) {
        await loadMarkerHistory(household.id, selectedMarkerName)
      }
      setEditingBloodTestId(null)
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const deleteBloodTest = async (bloodTestId: string) => {
    if (!household) {
      return
    }

    setSetupState('saving')

    try {
      await apiNoContent(`/api/households/${household.id}/health/blood-tests/${bloodTestId}`, { method: 'DELETE' })
      await loadHealthOverview(household.id)
      if (selectedMarkerName) {
        await loadMarkerHistory(household.id, selectedMarkerName)
      }
      setEditingBloodTestId((current) => (current === bloodTestId ? null : current))
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const uploadHealthDocument = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (!household || !documentFile) {
      return
    }

    const body = new FormData()
    body.append('file', documentFile)
    body.append('documentType', 'lab_result')

    if (documentMemberId) {
      body.append('memberId', documentMemberId)
    }

    setSetupState('saving')

    try {
      await apiFormData<{ id: string }>(`/api/households/${household.id}/health/documents`, body)
      await loadHealthDocuments(household.id)
      setDocumentFile(null)
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

  const formatFileSize = (size: number) => {
    if (size < 1024 * 1024) {
      return `${Math.max(1, Math.round(size / 1024)).toLocaleString('pl-PL')} KB`
    }

    return `${(size / 1024 / 1024).toLocaleString('pl-PL', { maximumFractionDigits: 1 })} MB`
  }

  const documentNameById = (documentId: string | null) => {
    if (!documentId) {
      return null
    }

    return healthDocuments.find((document) => document.id === documentId)?.originalName ?? 'Uploaded document'
  }

  const pln = (amount: number) => `${amount.toLocaleString('pl-PL', { maximumFractionDigits: 2 })} PLN`
  const activeHomeTasks = homeTasks.filter((task) => task.status === 'active')
  const completedHomeTasks = homeTasks.filter((task) => task.status === 'completed')
  const overdueHomeTasks = activeHomeTasks.filter((task) => task.nextDueAt < today)
  const upcomingHomeTasks = activeHomeTasks.filter((task) => {
    const dueTime = new Date(`${task.nextDueAt}T00:00:00`).getTime()
    const todayTime = new Date(`${today}T00:00:00`).getTime()
    const daysUntilDue = (dueTime - todayTime) / (1000 * 60 * 60 * 24)

    return daysUntilDue >= 0 && daysUntilDue <= 14
  })
  const laterHomeTasks = activeHomeTasks.filter((task) => !overdueHomeTasks.includes(task) && !upcomingHomeTasks.includes(task))
  const recurringHomeTasks = homeTasks.filter((task) => task.recurrenceType !== 'none')
  const homeTaskGroups = [
    { label: 'Overdue', tasks: overdueHomeTasks, tone: 'danger' },
    { label: 'Next 14 days', tasks: upcomingHomeTasks, tone: 'warning' },
    { label: 'Later', tasks: laterHomeTasks, tone: 'calm' },
    { label: 'Completed', tasks: completedHomeTasks, tone: 'done' },
  ]
  const billChecklistItems = [
    ...(expenseOverview?.billChecklist.overdue ?? []),
    ...(expenseOverview?.billChecklist.upcoming ?? []),
    ...(expenseOverview?.billChecklist.paid ?? []),
    ...(expenseOverview?.billChecklist.skipped ?? []),
  ]
  const hasBudgetWarning = (expenseOverview?.budgetUsage ?? []).some((row) => row.overBudget)
  const hasOverdueBills = (expenseOverview?.billChecklist.overdue.length ?? 0) > 0
  const balanceTone = (expenseOverview?.projectedMonthEndBalance ?? 0) < 0 ? 'danger' : 'good'
  const monthlyTrend = expenseOverview?.monthlyTrend ?? []
  const dailySpending = expenseOverview?.dailySpending ?? []
  const financeReview = expenseOverview?.review
  const overBudgetRows = (expenseOverview?.budgetUsage ?? []).filter((row) => row.overBudget)
  const budgetSuggestionRows = (expenseOverview?.budgetUsage ?? [])
    .filter((row) => row.spent > 0 && (row.budget === 0 || row.overBudget))
    .slice(0, 5)
  const plannedBillCount = expenseOverview?.billChecklist.upcoming.length ?? 0
  const skippedBillCount = expenseOverview?.billChecklist.skipped.length ?? 0
  const paidBillCount = expenseOverview?.billChecklist.paid.length ?? 0
  const otherIncomeEntries = (expenseOverview?.incomeEntries ?? []).filter((entry) => entry.incomeKind === 'other')
  const pendingIncomeReviewCount = financeReview?.incomeNeedsReviewCount ?? 0
  const monthDataIsClean = (financeReview?.needsReviewCount ?? 0) === 0 && pendingIncomeReviewCount === 0
  const monthHasOpenBills = hasOverdueBills || plannedBillCount > 0
  const monthResultTone = !monthDataIsClean ? 'warning' : (expenseOverview?.projectedMonthEndBalance ?? 0) < 0 || hasBudgetWarning || monthHasOpenBills ? 'danger' : 'good'
  const monthResultLabel = !monthDataIsClean
    ? 'Data needs cleanup'
    : (expenseOverview?.projectedMonthEndBalance ?? 0) < 0 || hasBudgetWarning || monthHasOpenBills
      ? 'Needs attention'
      : 'Good month'
  const suggestedBudgetAmount = (spent: number) => Math.ceil((spent * 1.05) / 50) * 50
  const maxTrendAmount = Math.max(1, ...monthlyTrend.flatMap((row) => [row.income, row.expense]))
  const maxDailyAmount = Math.max(1, ...dailySpending.map((row) => row.expense))
  const dailyChartWidth = 640
  const dailyChartHeight = 170
  const dailyYAxisTicks = [maxDailyAmount, maxDailyAmount / 2, 0]
  const dailyChartPoints = dailySpending.map((row, index) => {
    const x = dailySpending.length === 1 ? dailyChartWidth / 2 : (index / (dailySpending.length - 1)) * dailyChartWidth
    const y = dailyChartHeight - (row.expense / maxDailyAmount) * dailyChartHeight
    return `${x},${y}`
  }).join(' ')

  const healthTests = healthOverview?.latestBloodTests ?? []
  const allHealthMarkers = healthTests.flatMap((test) => test.markers)
  const latestTest = healthTests[0] ?? null
  const needsReviewMarkers = allHealthMarkers.filter((marker) => (
    marker.status === 'unknown'
    || marker.unit.trim() === ''
    || (marker.referenceMin !== null && marker.referenceMax !== null && marker.referenceMin > marker.referenceMax)
  ))
  const recentlyChangedMarkers = healthOverview?.markerNames
    .map((markerName) => {
      const values = allHealthMarkers
        .filter((marker) => marker.name === markerName)
        .sort((left, right) => right.testedAt.localeCompare(left.testedAt))

      if (values.length < 2) {
        return null
      }

      return {
        name: markerName,
        latest: values[0],
        change: values[0].value - values[1].value,
      }
    })
    .filter((item): item is { name: string, latest: BloodTestMarker, change: number } => item !== null)
    .sort((left, right) => Math.abs(right.change) - Math.abs(left.change))
    .slice(0, 4) ?? []
  const staleMarkerNames = (healthOverview?.markerNames ?? []).filter((markerName) => {
    const newest = allHealthMarkers
      .filter((marker) => marker.name === markerName)
      .sort((left, right) => right.testedAt.localeCompare(left.testedAt))[0]

    if (!newest) {
      return false
    }

    const daysSince = (Date.now() - new Date(newest.testedAt).getTime()) / (1000 * 60 * 60 * 24)

    return daysSince > 365
  }).slice(0, 6)

  const trendMarkers = [...markerHistory].reverse()
  const latestMarker = markerHistory[0] ?? null
  const previousMarker = markerHistory[1] ?? null
  const markerValues = trendMarkers.map((marker) => marker.value)
  const markerReferenceValues = trendMarkers.flatMap((marker) => [marker.referenceMin, marker.referenceMax]).filter((value): value is number => value !== null)
  const markerChartValues = [...markerValues, ...markerReferenceValues]
  const markerChartMin = markerChartValues.length > 0 ? Math.min(...markerChartValues) : 0
  const markerChartMax = markerChartValues.length > 0 ? Math.max(...markerChartValues) : 0
  const markerChartPadding = markerChartMax === markerChartMin ? 1 : (markerChartMax - markerChartMin) * 0.15
  const markerChartFloor = markerChartMin - markerChartPadding
  const markerChartCeiling = markerChartMax + markerChartPadding
  const markerChartRange = markerChartCeiling - markerChartFloor || 1
  const markerChartWidth = 640
  const markerChartHeight = 220
  const markerChartX = (index: number) => trendMarkers.length === 1 ? markerChartWidth / 2 : (index / (trendMarkers.length - 1)) * markerChartWidth
  const markerChartY = (value: number) => markerChartHeight - ((value - markerChartFloor) / markerChartRange) * markerChartHeight
  const markerChartPoints = trendMarkers.map((marker, index) => `${markerChartX(index)},${markerChartY(marker.value)}`).join(' ')
  const latestReferenceMin = latestMarker?.referenceMin ?? null
  const latestReferenceMax = latestMarker?.referenceMax ?? null
  const referenceMinY = latestReferenceMin === null ? null : markerChartY(latestReferenceMin)
  const referenceMaxY = latestReferenceMax === null ? null : markerChartY(latestReferenceMax)
  const markerChange = latestMarker && previousMarker ? latestMarker.value - previousMarker.value : null
  const attentionGroups = {
    critical: dashboard.attention.filter((item) => item.severity === 'critical'),
    warning: dashboard.attention.filter((item) => item.severity === 'warning'),
    info: dashboard.attention.filter((item) => item.severity === 'info'),
  }
  const attentionGroupLabels: Record<Dashboard['attention'][number]['severity'], string> = {
    critical: 'Critical',
    warning: 'Review',
    info: 'Info',
  }
  const categoryBySlug = (slug: string) => expenseOverview?.categories.find((category) => category.slug === slug) ?? null
  const countExpenseMatches = (matchText: string) => (financeReview?.expenseCandidates ?? [])
    .filter((expense) => expense.description.toLocaleLowerCase('pl-PL').includes(matchText.toLocaleLowerCase('pl-PL')))
    .length
  const countIncomeMatches = (matchText: string) => (financeReview?.incomeCandidates ?? [])
    .filter((entry) => entry.description.toLocaleLowerCase('pl-PL').includes(matchText.toLocaleLowerCase('pl-PL')))
    .length
  const suggestedFinanceRules = [
    { targetType: 'expense' as const, matchText: 'BIEDRONKA', label: 'BIEDRONKA to Groceries/Home', category: categoryBySlug('groceries-home'), incomeKind: null, count: countExpenseMatches('BIEDRONKA') },
    { targetType: 'expense' as const, matchText: 'DINO', label: 'DINO to Groceries/Home', category: categoryBySlug('groceries-home'), incomeKind: null, count: countExpenseMatches('DINO') },
    { targetType: 'expense' as const, matchText: 'MOYA', label: 'MOYA to Transport', category: categoryBySlug('transport'), incomeKind: null, count: countExpenseMatches('MOYA') },
    { targetType: 'expense' as const, matchText: 'ORLEN', label: 'ORLEN to Transport', category: categoryBySlug('transport'), incomeKind: null, count: countExpenseMatches('ORLEN') },
    { targetType: 'expense' as const, matchText: 'Gmina', label: 'Gmina to Bills', category: categoryBySlug('bills'), incomeKind: null, count: countExpenseMatches('Gmina') },
    { targetType: 'expense' as const, matchText: 'OBI', label: 'OBI to Other', category: categoryBySlug('other'), incomeKind: null, count: countExpenseMatches('OBI') },
    { targetType: 'income' as const, matchText: 'Amazon', label: 'Amazon positives to Refund', category: null, incomeKind: 'refund' as const, count: countIncomeMatches('Amazon') },
    { targetType: 'income' as const, matchText: 'Przelew środków', label: 'Internal transfers to Transfer', category: null, incomeKind: 'transfer' as const, count: countIncomeMatches('Przelew środków') },
  ].map((rule) => {
    const rows = rule.targetType === 'expense'
      ? (financeReview?.expenseCandidates ?? []).filter((expense) => expense.description.toLocaleLowerCase('pl-PL').includes(rule.matchText.toLocaleLowerCase('pl-PL')))
      : (financeReview?.incomeCandidates ?? []).filter((entry) => entry.description.toLocaleLowerCase('pl-PL').includes(rule.matchText.toLocaleLowerCase('pl-PL')))
    const total = rows.reduce((sum, row) => sum + row.amount, 0)

    return { ...rule, rows, total }
  }).filter((rule) => rule.count > 0 && (rule.targetType === 'income' || rule.category))

  const openAttentionTarget = (item: Dashboard['attention'][number]) => {
    setActivePage(item.targetPage)
    window.location.hash = item.targetPage

    if (item.targetPage === 'expenses' && item.targetSection) {
      setExpenseSection(item.targetSection)
    }
  }

  const openExpensesSection = (section: ExpenseSection) => {
    setActivePage('expenses')
    setExpenseSection(section)
    window.location.hash = 'expenses'
  }

  const modules = [
    {
      title: 'Home',
      value: dashboard.summary.homeTasksDue,
      label: 'tasks due',
      detail: `${homeTasks.length} maintenance tasks tracked`,
    },
    {
      title: 'Expenses',
      value: `${dashboard.summary.monthlySpend.toLocaleString('pl-PL')} PLN`,
      label: 'this month',
      detail: `${dashboard.summary.projectedBalance.toLocaleString('pl-PL')} PLN projected balance`,
    },
    {
      title: 'Health',
      value: dashboard.summary.healthMarkersTracked,
      label: 'markers tracked',
      detail: `${dashboard.summary.healthOutOfRange} out of range`,
    },
    {
      title: 'Documents',
      value: dashboard.summary.documentsStored,
      label: 'stored',
      detail: 'Contracts, manuals, invoices, lab PDFs',
    },
  ]

  const dailyActionCards = [
    {
      title: 'Review imported money',
      detail: `${financeReview?.needsReviewCount ?? 0} row${(financeReview?.needsReviewCount ?? 0) === 1 ? '' : 's'} need trust check`,
      tone: (financeReview?.needsReviewCount ?? 0) > 0 ? 'warning' : 'good',
      action: 'Open review',
      onClick: () => openExpensesSection('import-review'),
    },
    {
      title: 'Bills this month',
      detail: `${expenseOverview?.billChecklist.overdue.length ?? 0} overdue · ${expenseOverview?.billChecklist.upcoming.length ?? 0} upcoming`,
      tone: hasOverdueBills ? 'danger' : plannedBillCount > 0 ? 'warning' : 'good',
      action: 'Open bills',
      onClick: () => openExpensesSection('bills'),
    },
    {
      title: 'Home maintenance',
      detail: `${overdueHomeTasks.length} overdue · ${upcomingHomeTasks.length} upcoming`,
      tone: overdueHomeTasks.length > 0 ? 'danger' : upcomingHomeTasks.length > 0 ? 'warning' : 'good',
      action: 'Open home',
      onClick: () => {
        setActivePage('home')
        window.location.hash = 'home'
      },
    },
    {
      title: 'Health signals',
      detail: `${dashboard.summary.healthOutOfRange} out of range · ${needsReviewMarkers.length} to clean`,
      tone: dashboard.summary.healthOutOfRange > 0 ? 'danger' : needsReviewMarkers.length > 0 ? 'warning' : 'good',
      action: 'Open health',
      onClick: () => {
        setActivePage('health')
        window.location.hash = 'health'
      },
    },
    {
      title: 'Monthly money review',
      detail: monthResultLabel,
      tone: monthResultTone,
      action: 'Open checklist',
      onClick: () => openExpensesSection('monthly-review'),
    },
  ]

  const navItems: Array<{ page: AppPage; label: string }> = [
    { page: 'dashboard', label: 'Dashboard' },
    { page: 'household', label: 'Household' },
    { page: 'home', label: 'Home' },
    { page: 'expenses', label: 'Expenses' },
    { page: 'health', label: 'Health' },
    { page: 'documents', label: 'Documents' },
  ]

  const expenseSections: Array<{ section: ExpenseSection; label: string; meta: string }> = [
    { section: 'overview', label: 'Overview', meta: 'Monthly status' },
    { section: 'monthly-review', label: 'Monthly Review', meta: monthResultLabel },
    { section: 'analytics', label: 'Analytics', meta: 'Graphs and trends' },
    { section: 'transactions', label: 'Transactions', meta: 'Income and spending data' },
    { section: 'import-review', label: 'Import Review', meta: `${financeReview?.needsReviewCount ?? 0} to check` },
    { section: 'budgets', label: 'Budgets', meta: 'Category limits' },
    { section: 'bills', label: 'Bills', meta: 'Recurring checklist' },
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
    home: {
      eyebrow: 'Home',
      title: 'Maintenance tasks that keep the house running.',
      copy: 'Track one-time and recurring home work, then let the Dashboard remind you what needs action.',
    },
    expenses: {
      eyebrow: 'Expenses',
      title: 'Track household spending in PLN.',
      copy: 'Add daily costs, monthly bills, and see what is happening this month.',
    },
    health: {
      eyebrow: 'Health',
      title: 'Blood tests and health markers.',
      copy: 'Store family lab results, watch out-of-range markers, and build history over time.',
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

            <section className="daily-command-grid" aria-label="Daily command center">
              <article className="daily-command-card quick-expense-card">
                <div>
                  <p className="eyebrow">Fast Capture</p>
                  <h2>Add expense in under 10 seconds.</h2>
                  <p>Capture the cost now. You can clean categories and reports later in review.</p>
                </div>

                <form className="setup-form dashboard-expense-form" onSubmit={addExpense}>
                  <label>
                    What was it?
                    <input
                      value={expenseDescription}
                      onChange={(event) => setExpenseDescription(event.target.value)}
                      placeholder="Coffee, pharmacy, groceries..."
                      required
                    />
                  </label>
                  <label>
                    Amount
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      inputMode="decimal"
                      value={expenseAmount}
                      onChange={(event) => setExpenseAmount(event.target.value)}
                      placeholder="0.00"
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
                  <button type="submit" disabled={setupState === 'saving' || !expenseOverview?.categories.length}>
                    {setupState === 'saving' ? 'Saving...' : 'Save expense'}
                  </button>
                </form>
              </article>

              <article className="daily-command-card">
                <div>
                  <p className="eyebrow">Today Flow</p>
                  <h2>Do the smallest useful next step.</h2>
                  <p>These shortcuts keep the daily habit focused on review, bills, health, and the monthly result.</p>
                </div>

                <div className="daily-action-list">
                  {dailyActionCards.map((item) => (
                    <button className={`daily-action-item ${item.tone}`} type="button" onClick={item.onClick} key={item.title}>
                      <span>
                        <strong>{item.title}</strong>
                        <small>{item.detail}</small>
                      </span>
                      <b>{item.action}</b>
                    </button>
                  ))}
                </div>
              </article>
            </section>

            <section className="focus-panel">
              <div>
                <p className="eyebrow">Needs Attention</p>
                <h2>Start with the next important things.</h2>
              </div>

              <div className="attention-list">
                {dashboard.attention.length > 0 ? (
                  (Object.keys(attentionGroups) as Array<Dashboard['attention'][number]['severity']>).map((severity) => (
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
                      <span>Finance and health have no urgent review items right now.</span>
                    </div>
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

        {activePage === 'home' && (
          <section className="home-panel page-card">
            <div className="section-heading">
              <div>
                <p className="eyebrow">Maintenance</p>
                <h2>Home actions by due date.</h2>
              </div>
              <button type="button" onClick={() => setOpenHomeTaskCreator(!openHomeTaskCreator)}>
                {openHomeTaskCreator ? 'Close form' : 'Add task'}
              </button>
            </div>

            <div className="home-summary-grid">
              <article className={overdueHomeTasks.length > 0 ? 'danger' : 'good'}>
                <span>Overdue</span>
                <strong>{overdueHomeTasks.length}</strong>
              </article>
              <article>
                <span>Next 14 days</span>
                <strong>{upcomingHomeTasks.length}</strong>
              </article>
              <article>
                <span>Recurring</span>
                <strong>{recurringHomeTasks.length}</strong>
              </article>
              <article>
                <span>Completed</span>
                <strong>{completedHomeTasks.length}</strong>
              </article>
            </div>

            {openHomeTaskCreator && (
              <section className="home-task-create-panel">
                <form className="setup-form home-task-form" onSubmit={addHomeTask}>
                  <label>
                    Task
                    <input value={homeTaskTitle} onChange={(event) => setHomeTaskTitle(event.target.value)} placeholder="Replace heat pump filter" required />
                  </label>
                  <label>
                    Area
                    <input value={homeTaskArea} onChange={(event) => setHomeTaskArea(event.target.value)} placeholder="Heating, garden, kitchen..." required />
                  </label>
                  <label>
                    Due date
                    <input type="date" value={homeTaskNextDueAt} onChange={(event) => setHomeTaskNextDueAt(event.target.value)} required />
                  </label>
                  <label>
                    Repeat
                    <select value={homeTaskRecurrenceType} onChange={(event) => setHomeTaskRecurrenceType(event.target.value as HomeMaintenanceTask['recurrenceType'])}>
                      <option value="none">One-time</option>
                      <option value="daily">Daily</option>
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                      <option value="yearly">Yearly</option>
                    </select>
                  </label>
                  <label>
                    Assigned to
                    <select value={homeTaskAssignedMemberId} onChange={(event) => setHomeTaskAssignedMemberId(event.target.value)}>
                      <option value="">Household</option>
                      {(household?.members ?? []).map((member) => (
                        <option value={member.id} key={member.id}>{member.displayName}</option>
                      ))}
                    </select>
                  </label>
                  <label>
                    Priority
                    <select value={homeTaskPriority} onChange={(event) => setHomeTaskPriority(event.target.value as HomeMaintenanceTask['priority'])}>
                      <option value="low">Low</option>
                      <option value="normal">Normal</option>
                      <option value="high">High</option>
                    </select>
                  </label>
                  <label className="home-task-notes">
                    Notes
                    <input value={homeTaskNotes} onChange={(event) => setHomeTaskNotes(event.target.value)} placeholder="Filter size, where tools are, phone number..." />
                  </label>
                  <button type="submit" disabled={setupState === 'saving'}>
                    Save task
                  </button>
                </form>
              </section>
            )}

            <div className="home-task-groups">
              {homeTaskGroups.map((group) => (
                <section className={`home-task-group ${group.tone}`} key={group.label}>
                  <div className="panel-heading-row">
                    <div>
                      <p className="eyebrow">{group.label}</p>
                      <h3>{group.tasks.length} task{group.tasks.length === 1 ? '' : 's'}</h3>
                    </div>
                  </div>

                  <div className="home-task-list">
                    {group.tasks.length > 0 ? (
                      group.tasks.map((task) => (
                        <article className={`home-task-item priority-${task.priority} status-${task.status}`} key={task.id}>
                          <span className="home-task-priority"></span>
                          <div className="home-task-main">
                            <div>
                              <strong>{task.title}</strong>
                              <small>
                                {task.area} · due {task.nextDueAt} · {task.recurrenceType === 'none' ? 'one-time' : task.recurrenceType}
                                {' · '}{memberNameById(task.assignedMemberId)}
                              </small>
                              {task.notes && <p>{task.notes}</p>}
                              {task.completedAt && <em>Last completed {new Date(task.completedAt).toLocaleDateString('pl-PL')}</em>}
                            </div>
                            <div className="row-actions">
                              {task.status === 'active' && (
                                <button type="button" onClick={() => completeHomeTask(task.id)} disabled={setupState === 'saving'}>
                                  Done
                                </button>
                              )}
                              <button type="button" onClick={() => startEditHomeTask(task)}>
                                Edit
                              </button>
                              <button type="button" onClick={() => deleteHomeTask(task.id)}>
                                Delete
                              </button>
                            </div>
                          </div>

                          {editingHomeTaskId === task.id && (
                            <form className="inline-edit-form home-task-edit-form" onSubmit={updateHomeTask}>
                              <label>
                                Task
                                <input value={editHomeTaskTitle} onChange={(event) => setEditHomeTaskTitle(event.target.value)} required />
                              </label>
                              <label>
                                Area
                                <input value={editHomeTaskArea} onChange={(event) => setEditHomeTaskArea(event.target.value)} required />
                              </label>
                              <label>
                                Due date
                                <input type="date" value={editHomeTaskNextDueAt} onChange={(event) => setEditHomeTaskNextDueAt(event.target.value)} required />
                              </label>
                              <label>
                                Repeat
                                <select value={editHomeTaskRecurrenceType} onChange={(event) => setEditHomeTaskRecurrenceType(event.target.value as HomeMaintenanceTask['recurrenceType'])}>
                                  <option value="none">One-time</option>
                                  <option value="daily">Daily</option>
                                  <option value="weekly">Weekly</option>
                                  <option value="monthly">Monthly</option>
                                  <option value="yearly">Yearly</option>
                                </select>
                              </label>
                              <label>
                                Assigned to
                                <select value={editHomeTaskAssignedMemberId} onChange={(event) => setEditHomeTaskAssignedMemberId(event.target.value)}>
                                  <option value="">Household</option>
                                  {(household?.members ?? []).map((member) => (
                                    <option value={member.id} key={member.id}>{member.displayName}</option>
                                  ))}
                                </select>
                              </label>
                              <label>
                                Priority
                                <select value={editHomeTaskPriority} onChange={(event) => setEditHomeTaskPriority(event.target.value as HomeMaintenanceTask['priority'])}>
                                  <option value="low">Low</option>
                                  <option value="normal">Normal</option>
                                  <option value="high">High</option>
                                </select>
                              </label>
                              <label className="home-task-notes">
                                Notes
                                <input value={editHomeTaskNotes} onChange={(event) => setEditHomeTaskNotes(event.target.value)} />
                              </label>
                              <div className="inline-edit-actions">
                                <button type="submit" disabled={setupState === 'saving'}>Save</button>
                                <button type="button" onClick={() => setEditingHomeTaskId(null)}>Cancel</button>
                              </div>
                            </form>
                          )}
                        </article>
                      ))
                    ) : (
                      <p className="empty-state">No {group.label.toLocaleLowerCase('en-US')} maintenance tasks.</p>
                    )}
                  </div>
                </section>
              ))}
            </div>

            {setupState === 'error' && <p className="form-error">Could not save home maintenance data.</p>}
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
                <strong>{pln(expenseOverview?.monthTotal ?? 0)}</strong>
              </div>
            </div>

            {expenseSection === 'overview' && (
            <div className="money-control-grid">
              <article className="good">
                <span>Income</span>
                <strong>{pln(expenseOverview?.actualIncome || expenseOverview?.expectedIncome || 0)}</strong>
                <small>{pln(expenseOverview?.expectedIncome ?? 0)} expected</small>
              </article>
              <article className={hasBudgetWarning ? 'danger' : ''}>
                <span>Spent</span>
                <strong>{pln(expenseOverview?.spentTotal ?? 0)}</strong>
                <small>{hasBudgetWarning ? 'Budget warning' : 'Inside budget'}</small>
              </article>
              <article className={hasOverdueBills ? 'danger' : ''}>
                <span>Planned bills</span>
                <strong>{pln(expenseOverview?.recurringPlannedTotal ?? 0)}</strong>
                <small>{hasOverdueBills ? `${expenseOverview?.billChecklist.overdue.length ?? 0} overdue` : `${expenseOverview?.billChecklist.upcoming.length ?? 0} upcoming`}</small>
              </article>
              <article className={balanceTone}>
                <span>Remaining</span>
                <strong>{pln(expenseOverview?.remainingMonthlyMoney ?? 0)}</strong>
                <small>After spending and planned bills</small>
              </article>
              <article className={balanceTone}>
                <span>Projected balance</span>
                <strong>{pln(expenseOverview?.projectedMonthEndBalance ?? 0)}</strong>
                <small>Month-end estimate</small>
              </article>
            </div>
            )}

            <section className="expense-filters" aria-label="Expense filters">
              <label>
                Month
                <input type="month" value={expenseFilterMonth} onChange={(event) => setExpenseFilterMonth(event.target.value)} />
              </label>
              <label>
                Category
                <select value={expenseFilterCategoryId} onChange={(event) => setExpenseFilterCategoryId(event.target.value)}>
                  <option value="">All categories</option>
                  {(expenseOverview?.categories ?? []).map((category) => (
                    <option value={category.id} key={category.id}>{category.name}</option>
                  ))}
                </select>
              </label>
              <label>
                Paid by
                <select value={expenseFilterPaidByMemberId} onChange={(event) => setExpenseFilterPaidByMemberId(event.target.value)}>
                  <option value="">Everyone</option>
                  {(household?.members ?? []).map((member) => (
                    <option value={member.id} key={member.id}>{member.displayName}</option>
                  ))}
                </select>
              </label>
              <button
                type="button"
                onClick={() => {
                  setExpenseFilterMonth(currentMonth)
                  setExpenseFilterCategoryId('')
                  setExpenseFilterPaidByMemberId('')
                }}
              >
                Reset
              </button>
            </section>

            <nav className="expense-section-tabs" aria-label="Expense sections">
              {expenseSections.map((item) => (
                <button
                  type="button"
                  className={expenseSection === item.section ? 'active' : ''}
                  onClick={() => setExpenseSection(item.section)}
                  key={item.section}
                >
                  <strong>{item.label}</strong>
                  <span>{item.meta}</span>
                </button>
              ))}
            </nav>

            {expenseSection === 'overview' && (
              <section className="expense-overview-panel">
                <div className="overview-alert-grid">
                  <article className={hasBudgetWarning ? 'danger' : 'good'}>
                    <span>Budgets</span>
                    <strong>{hasBudgetWarning ? 'Needs attention' : 'Looks calm'}</strong>
                    <small>{(expenseOverview?.budgetUsage ?? []).filter((row) => row.overBudget).length} categories over budget</small>
                  </article>
                  <article className={hasOverdueBills ? 'danger' : 'good'}>
                    <span>Bills</span>
                    <strong>{hasOverdueBills ? 'Overdue' : 'On track'}</strong>
                    <small>{expenseOverview?.billChecklist.overdue.length ?? 0} overdue this month</small>
                  </article>
                  <article className={(financeReview?.needsReviewCount ?? 0) > 0 ? 'warning' : 'good'}>
                    <span>Import quality</span>
                    <strong>{financeReview?.needsReviewCount ?? 0} to review</strong>
                    <small>Imported rows before trusting reports</small>
                  </article>
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
              </section>
            )}

            {expenseSection === 'monthly-review' && (
              <section className="monthly-review-panel" aria-label="Monthly finance review">
                <article className={`monthly-result-card ${monthResultTone}`}>
                  <div>
                    <p className="eyebrow">Month Result</p>
                    <h3>{monthResultLabel}</h3>
                    <p>
                      Income {pln(expenseOverview?.actualIncome ?? 0)} · Spending {pln(expenseOverview?.spentTotal ?? 0)} · Projected {pln(expenseOverview?.projectedMonthEndBalance ?? 0)}
                    </p>
                  </div>
                  <strong>{pln(expenseOverview?.remainingMonthlyMoney ?? 0)}</strong>
                </article>

                <div className="monthly-review-grid">
                  <article className={(financeReview?.needsReviewCount ?? 0) > 0 ? 'warning' : 'good'}>
                    <span>1</span>
                    <div>
                      <h3>Review imports</h3>
                      <p>{financeReview?.needsReviewCount ?? 0} imported row{(financeReview?.needsReviewCount ?? 0) === 1 ? '' : 's'} still need cleanup.</p>
                    </div>
                    <button type="button" onClick={() => setExpenseSection('import-review')}>
                      Open import review
                    </button>
                  </article>

                  <article className={pendingIncomeReviewCount > 0 || otherIncomeEntries.length > 0 ? 'warning' : 'good'}>
                    <span>2</span>
                    <div>
                      <h3>Confirm income</h3>
                      <p>
                        {pendingIncomeReviewCount} income row{pendingIncomeReviewCount === 1 ? '' : 's'} need review. {otherIncomeEntries.length} row{otherIncomeEntries.length === 1 ? '' : 's'} are still marked as other income.
                      </p>
                    </div>
                    <button type="button" onClick={() => setExpenseSection('transactions')}>
                      Open income
                    </button>
                  </article>

                  <article className={hasBudgetWarning ? 'danger' : 'good'}>
                    <span>3</span>
                    <div>
                      <h3>Check budgets</h3>
                      <p>{overBudgetRows.length} categor{overBudgetRows.length === 1 ? 'y is' : 'ies are'} over budget.</p>
                    </div>
                    <button type="button" onClick={() => setExpenseSection('budgets')}>
                      Open budgets
                    </button>
                  </article>

                  <article className={monthHasOpenBills ? 'warning' : 'good'}>
                    <span>4</span>
                    <div>
                      <h3>Check bills</h3>
                      <p>{paidBillCount} paid · {plannedBillCount} planned · {skippedBillCount} skipped · {expenseOverview?.billChecklist.overdue.length ?? 0} overdue.</p>
                    </div>
                    <button type="button" onClick={() => setExpenseSection('bills')}>
                      Open bills
                    </button>
                  </article>
                </div>

                <section className="monthly-suggestion-panel">
                  <div>
                    <p className="eyebrow">Next Month</p>
                    <h3>Suggested budget updates</h3>
                  </div>
                  <div className="monthly-suggestion-list">
                    {budgetSuggestionRows.length > 0 ? (
                      budgetSuggestionRows.map((row) => (
                        <article key={row.category.id}>
                          <span style={{ background: row.category.color }}></span>
                          <div>
                            <strong>{row.category.name}</strong>
                            <small>Spent {pln(row.spent)} · current budget {pln(row.budget)}</small>
                          </div>
                          <b>{pln(suggestedBudgetAmount(row.spent))}</b>
                        </article>
                      ))
                    ) : (
                      <p className="empty-state">No budget suggestions yet. Set category budgets and review a full month first.</p>
                    )}
                  </div>
                </section>
              </section>
            )}

            {expenseSection === 'analytics' && (
            <section className="expense-visual-grid" aria-label="Expense charts">
              <article className="expense-chart-card">
                <div>
                  <p className="eyebrow">Trend</p>
                  <h3>12-month cashflow</h3>
                </div>
                <div className="cashflow-chart">
                  {monthlyTrend.map((row) => (
                    <div className="cashflow-month" key={row.month}>
                      <div className="cashflow-bars">
                        <span className="income-bar" style={{ height: `${Math.max(4, (row.income / maxTrendAmount) * 100)}%` }} title={`Income ${pln(row.income)}`}></span>
                        <span className="expense-bar" style={{ height: `${Math.max(4, (row.expense / maxTrendAmount) * 100)}%` }} title={`Expenses ${pln(row.expense)}`}></span>
                      </div>
                      <small>{row.month.slice(5)}</small>
                    </div>
                  ))}
                </div>
                <div className="chart-legend">
                  <span><b className="legend-income"></b> Income</span>
                  <span><b className="legend-expense"></b> Expenses</span>
                </div>
              </article>

              <article className="expense-chart-card">
                <div>
                  <p className="eyebrow">Month rhythm</p>
                  <h3>Daily spending</h3>
                </div>
                {dailySpending.length > 0 ? (
                  <div className="daily-spending-chart">
                    <div className="daily-chart-body">
                      <div className="daily-y-axis" aria-hidden="true">
                        {dailyYAxisTicks.map((tick) => (
                          <span key={tick}>{pln(tick)}</span>
                        ))}
                      </div>
                      <svg viewBox={`0 0 ${dailyChartWidth} ${dailyChartHeight}`} role="img">
                        {[0, 0.5, 1].map((step) => (
                          <line key={step} x1="0" x2={dailyChartWidth} y1={dailyChartHeight * step} y2={dailyChartHeight * step} />
                        ))}
                        <polyline points={dailyChartPoints} />
                        {dailySpending.map((row, index) => {
                          const x = dailySpending.length === 1 ? dailyChartWidth / 2 : (index / (dailySpending.length - 1)) * dailyChartWidth
                          const y = dailyChartHeight - (row.expense / maxDailyAmount) * dailyChartHeight
                          return <circle key={row.date} cx={x} cy={y} r="5" />
                        })}
                      </svg>
                    </div>
                    <div className="marker-chart-axis">
                      <span>{dailySpending[0]?.date}</span>
                      <span>{dailySpending[dailySpending.length - 1]?.date}</span>
                    </div>
                  </div>
                ) : (
                  <p className="empty-state">No daily spending in this month.</p>
                )}
              </article>

              <article className="expense-chart-card category-bars-card">
                <div>
                  <p className="eyebrow">Categories</p>
                  <h3>Where money goes</h3>
                </div>
                <div className="category-bar-list">
                  {(expenseOverview?.topCategories ?? []).map((category) => {
                    const maxCategory = Math.max(1, ...(expenseOverview?.topCategories ?? []).map((row) => row.amount))
                    return (
                      <div key={category.name}>
                        <div><strong>{category.name}</strong><span>{pln(category.amount)}</span></div>
                        <p><span style={{ width: `${(category.amount / maxCategory) * 100}%`, background: category.color }}></span></p>
                      </div>
                    )
                  })}
                </div>
              </article>
            </section>
            )}

            {expenseSection === 'import-review' && (
            <section className="finance-review-panel" aria-label="Imported transaction review">
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">Review</p>
                  <h3>Imported transaction cleanup</h3>
                </div>
                <div className="review-score">
                  <strong>{financeReview?.needsReviewCount ?? 0}</strong>
                  <span>need review</span>
                </div>
              </div>

              <div className="review-stats">
                <article>
                  <span>Expenses to check</span>
                  <strong>{financeReview?.expenseNeedsReviewCount ?? 0}</strong>
                </article>
                <article>
                  <span>Income to classify</span>
                  <strong>{financeReview?.incomeNeedsReviewCount ?? 0}</strong>
                </article>
                <article>
                  <span>Excluded from income</span>
                  <strong>{pln(financeReview?.excludedIncomeTotal ?? 0)}</strong>
                </article>
              </div>

              <section className="bulk-rule-panel" aria-label="Suggested bulk review rules">
                <div className="panel-heading-row">
                  <div>
                    <p className="eyebrow">Bulk Cleanup</p>
                    <h4>Merchant groups</h4>
                  </div>
                  {financeReview?.lastAppliedBatch && (
                    <button type="button" onClick={undoLastFinanceReviewBatch} disabled={setupState === 'saving'}>
                      Undo {financeReview.lastAppliedBatch.matchText}
                    </button>
                  )}
                </div>
                <div className="bulk-rule-list">
                  {suggestedFinanceRules.length > 0 ? (
                    suggestedFinanceRules.map((rule) => (
                      <article key={`${rule.targetType}-${rule.matchText}`}>
                        <div>
                          <strong>{rule.label}</strong>
                          <small>{rule.count} row{rule.count === 1 ? '' : 's'} · {pln(rule.total)}</small>
                          <em>
                            {rule.rows.slice(0, 3).map((row) => `${row.description} ${pln(row.amount)}`).join(' · ')}
                          </em>
                        </div>
                        <button
                          type="button"
                          onClick={() => applyFinanceReviewRule(rule.targetType, rule.matchText, rule.category?.id ?? null, rule.incomeKind)}
                          disabled={setupState === 'saving'}
                        >
                          Apply rule
                        </button>
                      </article>
                    ))
                  ) : (
                    <p className="empty-state">No repeated review patterns found for this month.</p>
                  )}
                </div>

                {(expenseOverview?.reviewRules ?? []).length > 0 && (
                  <div className="saved-rule-list">
                    <strong>Saved rules</strong>
                    {(expenseOverview?.reviewRules ?? []).slice(0, 6).map((rule) => (
                      <span key={rule.id}>
                        {rule.matchText} → {rule.targetType === 'expense'
                          ? expenseOverview?.categories.find((category) => category.id === rule.categoryId)?.name ?? 'Category'
                          : rule.incomeKind}
                      </span>
                    ))}
                  </div>
                )}
              </section>

              <div className="review-grid">
                <section>
                  <h4>Expense category check</h4>
                  <div className="review-list">
                    {(financeReview?.expenseCandidates ?? []).length > 0 ? (
                      financeReview?.expenseCandidates.map((expense) => (
                        <article className="review-item" key={expense.id}>
                          <span style={{ background: expense.category.color }}></span>
                          <div>
                            <strong>{expense.description}</strong>
                            <small>{expense.spentOn} · {pln(expense.amount)} · {expense.reviewReason ?? 'Imported row'}</small>
                          </div>
                          <select value={expense.category.id} onChange={(event) => changeExpenseCategory(expense, event.target.value)}>
                            {(expenseOverview?.categories ?? []).map((category) => (
                              <option value={category.id} key={category.id}>{category.name}</option>
                            ))}
                          </select>
                          <button type="button" onClick={() => patchExpenseReview(expense, { reviewStatus: 'reviewed', reviewReason: null })}>
                            Looks good
                          </button>
                        </article>
                      ))
                    ) : (
                      <p className="empty-state">No expense rows need review for this month.</p>
                    )}
                  </div>
                </section>

                <section>
                  <h4>Income type check</h4>
                  <div className="review-list">
                    {(financeReview?.incomeCandidates ?? []).length > 0 ? (
                      financeReview?.incomeCandidates.map((entry) => (
                        <article className={`review-item income-kind-${entry.incomeKind}`} key={entry.id}>
                          <span></span>
                          <div>
                            <strong>{entry.description}</strong>
                            <small>{entry.receivedOn} · {pln(entry.amount)} · {entry.reviewReason ?? 'Imported row'}</small>
                          </div>
                          <select
                            value={entry.incomeKind}
                            onChange={(event) => patchIncomeReview(entry, {
                              incomeKind: event.target.value as IncomeEntry['incomeKind'],
                              reviewStatus: 'reviewed',
                              reviewReason: null,
                            })}
                          >
                            <option value="salary">Salary</option>
                            <option value="transfer">Transfer</option>
                            <option value="refund">Refund</option>
                            <option value="other">Other income</option>
                          </select>
                          <button type="button" onClick={() => patchIncomeReview(entry, { reviewStatus: 'reviewed', reviewReason: null })}>
                            Looks good
                          </button>
                        </article>
                      ))
                    ) : (
                      <p className="empty-state">No income rows need review for this month.</p>
                    )}
                  </div>
                </section>
              </div>
            </section>
            )}

            {expenseSection === 'budgets' && (
            <section className="money-control-panel">
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">Budgets</p>
                  <h3>Category limits</h3>
                </div>
                <button type="button" onClick={() => setOpenMoneyPanel(openMoneyPanel === 'budgets' ? null : 'budgets')}>
                  {openMoneyPanel === 'budgets' ? 'Close budgets' : 'Edit budgets'}
                </button>
              </div>
              {openMoneyPanel === 'budgets' && (
                <form className="budget-edit-grid" onSubmit={saveBudgets}>
                  {(expenseOverview?.budgetUsage ?? []).map((row) => (
                    <label key={row.category.id}>{row.category.name}<input type="number" min="0" step="0.01" value={budgetDrafts[row.category.id] ?? ''} onChange={(event) => setBudgetDrafts((drafts) => ({ ...drafts, [row.category.id]: event.target.value }))} /></label>
                  ))}
                  <button type="submit" disabled={setupState === 'saving'}>Save budgets</button>
                </form>
              )}
              <div className="budget-list">
                {(expenseOverview?.budgetUsage ?? []).map((row) => (
                  <article className={row.overBudget ? 'over-budget' : ''} key={row.category.id}>
                    <div><strong>{row.category.name}</strong><small>{pln(row.spent)} of {pln(row.budget)}</small></div>
                    <div className="budget-bar"><span style={{ width: `${Math.min(100, row.percent)}%`, background: row.category.color }}></span></div>
                    <b>{pln(row.remaining)}</b>
                  </article>
                ))}
              </div>
            </section>
            )}

            {expenseSection === 'bills' && (
            <section className="money-control-panel">
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">Bills</p>
                  <h3>Monthly checklist</h3>
                </div>
              </div>
              <div className="bill-checklist">
                {billChecklistItems.length > 0 ? billChecklistItems.map((item) => (
                  <article className={`bill-status-${item.status} ${expenseOverview?.billChecklist.overdue.some((overdue) => overdue.bill.id === item.bill.id) ? 'overdue' : ''}`} key={`${item.bill.id}-${item.status}`}>
                    <span style={{ background: item.bill.category.color }}></span>
                    <div><strong>{item.bill.name}</strong><small>{item.bill.category.name} · due day {item.bill.dueDay} · {memberNameById(item.bill.paidByMemberId)}</small></div>
                    <b>{pln(item.amount)}</b>
                    <div className="row-actions">
                      <button type="button" onClick={() => updateBillPayment(item.bill.id, 'paid', item.amount)}>Mark paid</button>
                      <button type="button" onClick={() => updateBillPayment(item.bill.id, 'skipped', item.amount)}>Skip</button>
                      <button type="button" onClick={() => updateBillPayment(item.bill.id, 'planned', item.amount)}>Plan</button>
                      <button type="button" onClick={() => startEditBill(item.bill)}>Edit bill</button>
                      <button type="button" onClick={() => deleteRecurringBill(item.bill.id)}>Delete</button>
                    </div>
                    {editingBillId === item.bill.id && (
                      <form className="inline-edit-form bill-edit-form" onSubmit={updateRecurringBill}>
                        <label>
                          Name
                          <input value={editBillName} onChange={(event) => setEditBillName(event.target.value)} required />
                        </label>
                        <label>
                          Amount PLN
                          <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={editBillAmount}
                            onChange={(event) => setEditBillAmount(event.target.value)}
                            required
                          />
                        </label>
                        <label>
                          Category
                          <select value={editBillCategoryId} onChange={(event) => setEditBillCategoryId(event.target.value)} required>
                            {(expenseOverview?.categories ?? []).map((category) => (
                              <option value={category.id} key={category.id}>{category.name}</option>
                            ))}
                          </select>
                        </label>
                        <label>
                          Paid by
                          <select value={editBillPaidByMemberId} onChange={(event) => setEditBillPaidByMemberId(event.target.value)}>
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
                            value={editBillDueDay}
                            onChange={(event) => setEditBillDueDay(event.target.value)}
                            required
                          />
                        </label>
                        <div className="inline-edit-actions">
                          <button type="submit" disabled={setupState === 'saving'}>Save</button>
                          <button type="button" onClick={() => setEditingBillId(null)}>Cancel</button>
                        </div>
                      </form>
                    )}
                  </article>
                )) : <p className="empty-state">No recurring bills yet.</p>}
              </div>
            </section>
            )}

            {expenseSection === 'transactions' && (
            <>
            <section className="expense-create-bar" aria-label="Add expense data">
              <div>
                <h3>Add new data</h3>
                <p>Open a form only when you need to add a transaction or bill.</p>
              </div>
              <div className="expense-create-actions">
                <button
                  type="button"
                  className={openExpenseCreator === 'expense' ? 'active' : ''}
                  onClick={() => setOpenExpenseCreator(openExpenseCreator === 'expense' ? null : 'expense')}
                >
                  {openExpenseCreator === 'expense' ? 'Close expense' : 'Add expense'}
                </button>
                <button
                  type="button"
                  className={openExpenseCreator === 'bill' ? 'active' : ''}
                  onClick={() => setOpenExpenseCreator(openExpenseCreator === 'bill' ? null : 'bill')}
                >
                  {openExpenseCreator === 'bill' ? 'Close bill' : 'Add recurring bill'}
                </button>
              </div>
            </section>

            {openExpenseCreator && (
              <section className="expense-create-panel">
                {openExpenseCreator === 'expense' ? (
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
                ) : (
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
                )}
              </section>
            )}

            <section className="transaction-table-panel">
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">Expense Data</p>
                  <h3>Filtered expenses</h3>
                  <p className="panel-copy">
                    Showing {(expenseOverview?.latestExpenses ?? []).length} expense{(expenseOverview?.latestExpenses ?? []).length === 1 ? '' : 's'} for {expenseFilterMonth}
                    {expenseFilterCategoryId ? ` · ${(expenseOverview?.categories ?? []).find((category) => category.id === expenseFilterCategoryId)?.name ?? 'selected category'}` : ' · all categories'}
                    {expenseFilterPaidByMemberId ? ` · ${memberNameById(expenseFilterPaidByMemberId)}` : ''}.
                  </p>
                </div>
                <strong>{pln(expenseOverview?.spentTotal ?? 0)}</strong>
              </div>

              {(expenseOverview?.latestExpenses ?? []).length > 0 ? (
                <div className="transaction-table-wrap">
                  <table className="transaction-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Paid by</th>
                        <th className="amount-column">Amount</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(expenseOverview?.latestExpenses ?? []).map((expense) => (
                        <tr key={expense.id}>
                          <td>{expense.spentOn}</td>
                          <td>
                            <strong>{expense.description}</strong>
                            {expense.reviewStatus === 'needs_review' && <small>Needs review</small>}
                          </td>
                          <td>
                            <span className="category-pill">
                              <i style={{ background: expense.category.color }}></i>
                              {expense.category.name}
                            </span>
                          </td>
                          <td>{memberNameById(expense.paidByMemberId)}</td>
                          <td className="amount-column">{pln(expense.amount)}</td>
                          <td>
                            <div className="row-actions">
                              <button type="button" onClick={() => startEditExpense(expense)}>Edit</button>
                              <button type="button" onClick={() => deleteExpense(expense.id)}>Delete</button>
                            </div>
                            {editingExpenseId === expense.id && (
                              <form className="inline-edit-form transaction-edit-form" onSubmit={updateExpense}>
                                <label>
                                  Description
                                  <input value={editExpenseDescription} onChange={(event) => setEditExpenseDescription(event.target.value)} required />
                                </label>
                                <label>
                                  Amount PLN
                                  <input
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={editExpenseAmount}
                                    onChange={(event) => setEditExpenseAmount(event.target.value)}
                                    required
                                  />
                                </label>
                                <label>
                                  Category
                                  <select value={editExpenseCategoryId} onChange={(event) => setEditExpenseCategoryId(event.target.value)} required>
                                    {(expenseOverview?.categories ?? []).map((category) => (
                                      <option value={category.id} key={category.id}>{category.name}</option>
                                    ))}
                                  </select>
                                </label>
                                <label>
                                  Paid by
                                  <select value={editExpensePaidByMemberId} onChange={(event) => setEditExpensePaidByMemberId(event.target.value)}>
                                    <option value="">Household</option>
                                    {(household?.members ?? []).map((member) => (
                                      <option value={member.id} key={member.id}>{member.displayName}</option>
                                    ))}
                                  </select>
                                </label>
                                <label>
                                  Date
                                  <input type="date" value={editExpenseSpentOn} onChange={(event) => setEditExpenseSpentOn(event.target.value)} required />
                                </label>
                                <div className="inline-edit-actions">
                                  <button type="submit" disabled={setupState === 'saving'}>Save</button>
                                  <button type="button" onClick={() => setEditingExpenseId(null)}>Cancel</button>
                                </div>
                              </form>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <p className="empty-state">No expenses match the selected month, category, and paid-by filters.</p>
              )}
            </section>

            <section className="transaction-income-panel">
              <div className="panel-heading-row">
                <div>
                  <p className="eyebrow">Income</p>
                  <h3>Income management</h3>
                  <p className="panel-copy">Keep income separate from the expense table. Open these only when you need to add or remove income rows.</p>
                </div>
                <div className="row-actions">
                  <button type="button" onClick={() => setOpenMoneyPanel(openMoneyPanel === 'income-source' ? null : 'income-source')}>
                    Add recurring
                  </button>
                  <button type="button" onClick={() => setOpenMoneyPanel(openMoneyPanel === 'income-entry' ? null : 'income-entry')}>
                    Add one-time
                  </button>
                </div>
              </div>
              {openMoneyPanel === 'income-source' && (
                <form className="setup-form money-mini-form" onSubmit={addIncomeSource}>
                  <label>Name<input value={incomeSourceName} onChange={(event) => setIncomeSourceName(event.target.value)} required /></label>
                  <label>Amount PLN<input type="number" min="0.01" step="0.01" value={incomeSourceAmount} onChange={(event) => setIncomeSourceAmount(event.target.value)} required /></label>
                  <label>Member<select value={incomeSourceMemberId} onChange={(event) => setIncomeSourceMemberId(event.target.value)}><option value="">Household</option>{(household?.members ?? []).map((member) => <option value={member.id} key={member.id}>{member.displayName}</option>)}</select></label>
                  <button type="submit" disabled={setupState === 'saving'}>Save income</button>
                </form>
              )}
              {openMoneyPanel === 'income-entry' && (
                <form className="setup-form money-mini-form" onSubmit={addIncomeEntry}>
                  <label>Description<input value={incomeEntryDescription} onChange={(event) => setIncomeEntryDescription(event.target.value)} required /></label>
                  <label>Amount PLN<input type="number" min="0.01" step="0.01" value={incomeEntryAmount} onChange={(event) => setIncomeEntryAmount(event.target.value)} required /></label>
                  <label>Source<select value={incomeEntrySourceId} onChange={(event) => setIncomeEntrySourceId(event.target.value)}><option value="">One-time</option>{(expenseOverview?.incomeSources ?? []).map((source) => <option value={source.id} key={source.id}>{source.name}</option>)}</select></label>
                  <label>Member<select value={incomeEntryMemberId} onChange={(event) => setIncomeEntryMemberId(event.target.value)}><option value="">Household</option>{(household?.members ?? []).map((member) => <option value={member.id} key={member.id}>{member.displayName}</option>)}</select></label>
                  <label>Date<input type="date" value={incomeEntryReceivedOn} onChange={(event) => setIncomeEntryReceivedOn(event.target.value)} required /></label>
                  <button type="submit" disabled={setupState === 'saving'}>Save entry</button>
                </form>
              )}
              <div className="income-row-list">
                {(expenseOverview?.incomeSources ?? []).map((source) => (
                  <article key={source.id}>
                    <span style={{ background: '#18a67a' }}></span>
                    <div><strong>{source.name}</strong><small>{memberNameById(source.memberId)} · recurring</small></div>
                    <b>{pln(source.amount)}</b>
                    <button type="button" onClick={() => deleteIncomeSource(source.id)}>Delete</button>
                  </article>
                ))}
                {(expenseOverview?.incomeEntries ?? []).map((entry) => (
                  <article key={entry.id}>
                    <span style={{ background: '#0f766e' }}></span>
                    <div><strong>{entry.description}</strong><small>{memberNameById(entry.memberId)} · {entry.receivedOn}</small></div>
                    <b>{pln(entry.amount)}</b>
                    <button type="button" onClick={() => deleteIncomeEntry(entry.id)}>Delete</button>
                  </article>
                ))}
              </div>
            </section>
            </>
            )}

            {setupState === 'error' && <p className="form-error">Could not save expenses data.</p>}
          </section>
        )}

        {activePage === 'health' && (
          <section className="health-panel page-card">
            <datalist id="health-marker-catalog">
              {(healthOverview?.markerCatalog ?? []).map((marker) => (
                <option value={marker.name} key={marker.name}>
                  {marker.category} · {marker.unit}
                </option>
              ))}
            </datalist>

            <div className="section-heading">
              <div>
                <p className="eyebrow">Health</p>
                <h2>Blood tests, markers, and trends.</h2>
              </div>
              <div className="expense-total health-total">
                <span>Out of range</span>
                <strong>{healthOverview?.outOfRangeMarkers.length ?? 0}</strong>
              </div>
            </div>

            <p className="health-warning">
              Home OS stores and organizes your health data. It does not diagnose, so always use lab ranges and doctor guidance.
            </p>

            <div className="expense-summary-grid health-summary-grid">
              <article>
                <span>Blood tests</span>
                <strong>{healthOverview?.latestBloodTests.length ?? 0}</strong>
              </article>
              <article>
                <span>Tracked markers</span>
                <strong>{healthOverview?.markerNames.length ?? 0}</strong>
              </article>
              <article>
                <span>Family filter</span>
                <strong>{healthMemberFilterId ? memberNameById(healthMemberFilterId) : 'All'}</strong>
              </article>
              <article>
                <span>Needs review</span>
                <strong>{needsReviewMarkers.length}</strong>
              </article>
            </div>

            <section className="health-insight-grid" aria-label="Health overview">
              <article>
                <span>Latest result</span>
                <strong>{latestTest ? latestTest.testedAt : 'No result'}</strong>
                <small>{latestTest ? `${memberNameById(latestTest.memberId)} · ${latestTest.markers.length} markers` : 'Import a lab file to start history.'}</small>
              </article>
              <article>
                <span>Recently changed</span>
                <strong>{recentlyChangedMarkers.length > 0 ? recentlyChangedMarkers[0].name : 'No comparison'}</strong>
                <small>
                  {recentlyChangedMarkers.length > 0
                    ? `${recentlyChangedMarkers[0].change > 0 ? '+' : ''}${recentlyChangedMarkers[0].change.toLocaleString('pl-PL')} ${recentlyChangedMarkers[0].latest.unit}`
                    : 'Add at least two results for one marker.'}
                </small>
              </article>
              <article>
                <span>Old markers</span>
                <strong>{staleMarkerNames.length}</strong>
                <small>{staleMarkerNames.length > 0 ? staleMarkerNames.slice(0, 3).join(', ') : 'Nothing older than one year in loaded data.'}</small>
              </article>
              <article>
                <span>Review queue</span>
                <strong>{needsReviewMarkers.length > 0 ? needsReviewMarkers[0].name : 'Clean'}</strong>
                <small>{needsReviewMarkers.length > 0 ? 'Unknown status, missing unit, or suspicious range.' : 'Imported rows look structured.'}</small>
              </article>
            </section>

            <section className="expense-filters health-filters" aria-label="Health filters">
              <label>
                Family member
                <select value={healthMemberFilterId} onChange={(event) => setHealthMemberFilterId(event.target.value)}>
                  <option value="">Everyone</option>
                  {(household?.members ?? []).map((member) => (
                    <option value={member.id} key={member.id}>{member.displayName}</option>
                  ))}
                </select>
              </label>
              <button
                type="button"
                onClick={() => {
                  setHealthMemberFilterId('')
                }}
              >
                Reset
              </button>
            </section>

            <section className="expense-create-bar health-create-bar" aria-label="Add health data">
              <div>
                <h3>Add a blood test</h3>
                <p>Keep the page clean until you need to enter lab results.</p>
              </div>
              <div className="expense-create-actions">
                <button
                  type="button"
                  className={openBloodTestCreator ? 'active' : ''}
                  onClick={() => setOpenBloodTestCreator(!openBloodTestCreator)}
                >
                  {openBloodTestCreator ? 'Close form' : 'Add blood test'}
                </button>
              </div>
            </section>

            {openBloodTestCreator && (
              <section className="expense-create-panel">
                <form className="setup-form health-form" onSubmit={addBloodTest}>
                  <h3>Blood test details</h3>
                  <label>
                    Family member
                    <select value={bloodTestMemberId} onChange={(event) => setBloodTestMemberId(event.target.value)} required>
                      {(household?.members ?? []).map((member) => (
                        <option value={member.id} key={member.id}>{member.displayName}</option>
                      ))}
                    </select>
                  </label>
                  <label>
                    Test date
                    <input type="date" value={bloodTestTestedAt} onChange={(event) => setBloodTestTestedAt(event.target.value)} required />
                  </label>
                  <label>
                    Lab
                    <input value={bloodTestLabName} onChange={(event) => setBloodTestLabName(event.target.value)} placeholder="Diagnostyka" />
                  </label>
                  <label className="health-notes">
                    Notes
                    <input value={bloodTestNotes} onChange={(event) => setBloodTestNotes(event.target.value)} placeholder="Fasting, morning test..." />
                  </label>

                  <div className="marker-form-list">
                    <div className="marker-form-heading">
                      <h3>Markers</h3>
                      <button type="button" onClick={() => setMarkerRows((rows) => [...rows, createMarkerRow()])}>
                        Add marker
                      </button>
                    </div>
                    {markerRows.map((row) => (
                      <div className="marker-row" key={row.id}>
                        <label>
                          Marker
                          <input
                            value={row.markerName}
                            onChange={(event) => updateMarkerRow(row.id, 'markerName', event.target.value)}
                            list="health-marker-catalog"
                            placeholder="TSH"
                            required
                          />
                        </label>
                        <label>
                          Value
                          <input
                            type="number"
                            step="0.001"
                            value={row.value}
                            onChange={(event) => updateMarkerRow(row.id, 'value', event.target.value)}
                            required
                          />
                        </label>
                        <label>
                          Unit
                          <input
                            value={row.unit}
                            onChange={(event) => updateMarkerRow(row.id, 'unit', event.target.value)}
                            placeholder="mIU/L"
                            required
                          />
                        </label>
                        <label>
                          Ref min
                          <input
                            type="number"
                            step="0.001"
                            value={row.referenceMin}
                            onChange={(event) => updateMarkerRow(row.id, 'referenceMin', event.target.value)}
                          />
                        </label>
                        <label>
                          Ref max
                          <input
                            type="number"
                            step="0.001"
                            value={row.referenceMax}
                            onChange={(event) => updateMarkerRow(row.id, 'referenceMax', event.target.value)}
                          />
                        </label>
                        <label>
                          Status
                          <select value={row.status} onChange={(event) => updateMarkerRow(row.id, 'status', event.target.value)}>
                            <option value="unknown">Unknown</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                          </select>
                        </label>
                        <label className="marker-note-field">
                          Marker notes
                          <input value={row.notes} onChange={(event) => updateMarkerRow(row.id, 'notes', event.target.value)} />
                        </label>
                        <button type="button" className="remove-marker" onClick={() => removeMarkerRow(row.id)}>
                          Remove
                        </button>
                      </div>
                    ))}
                  </div>

                  <button type="submit" disabled={setupState === 'saving'}>
                    Save blood test
                  </button>
                </form>
              </section>
            )}

            <section className="health-documents-panel">
              <div className="section-heading">
                <div>
                  <p className="eyebrow">Documents</p>
                  <h2>Lab result files.</h2>
                  <p className="panel-copy">Upload PDFs or photos now. OCR import will use these originals in the next layer.</p>
                </div>
              </div>

              <form className="setup-form document-upload-form" onSubmit={uploadHealthDocument}>
                <label>
                  Family member
                  <select value={documentMemberId} onChange={(event) => setDocumentMemberId(event.target.value)}>
                    <option value="">Household</option>
                    {(household?.members ?? []).map((member) => (
                      <option value={member.id} key={member.id}>{member.displayName}</option>
                    ))}
                  </select>
                </label>
                <label>
                  PDF or photo
                  <input
                    type="file"
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    onChange={(event) => setDocumentFile(event.target.files?.[0] ?? null)}
                    required
                  />
                </label>
                <button type="submit" disabled={setupState === 'saving' || !documentFile}>
                  Upload file
                </button>
              </form>

              <div className="health-document-list">
                {healthDocuments.length > 0 ? (
                  healthDocuments.map((document) => (
                    <article className="health-document-item" key={document.id}>
                      <div>
                        <strong>{document.originalName}</strong>
                        <small>
                          {memberNameById(document.memberId)} · {formatFileSize(document.size)} ·{' '}
                          {new Date(document.uploadedAt).toLocaleDateString('pl-PL')}
                        </small>
                      </div>
                      <div className="health-document-actions">
                        <button type="button" onClick={() => startDocumentImport(document)}>
                          Import
                        </button>
                        <button type="button" onClick={() => extractHealthDocumentText(document)}>
                          Extract text
                        </button>
                        <a href={document.downloadUrl}>Download</a>
                      </div>
                    </article>
                  ))
                ) : (
                  <p className="empty-state">No health documents uploaded yet.</p>
                )}
              </div>

              {importDocument && (
                <form className="setup-form import-review-panel" onSubmit={saveDocumentImport}>
                  <div className="import-review-heading">
                    <div>
                      <p className="eyebrow">Review Import</p>
                      <h3>{importDocument.originalName}</h3>
                      <p>Enter or correct the data from this file before saving it as a blood test.</p>
                    </div>
                    <div className="import-review-actions">
                      <button type="button" onClick={() => extractHealthDocumentText(importDocument)} disabled={setupState === 'saving'}>
                        Extract text
                      </button>
                      <button type="button" onClick={() => setImportDocument(null)}>
                        Close
                      </button>
                    </div>
                  </div>

                  {(extractionStatus || extractedText) && (
                    <section className="extraction-preview">
                      <div>
                        <strong>Extracted text</strong>
                        <span>{extractionStatus || 'waiting'}</span>
                      </div>
                      {extractionMessage && <p>{extractionMessage}</p>}
                      {extractedText ? <pre>{extractedText}</pre> : <p>No text extracted yet.</p>}
                    </section>
                  )}

                  <div className="import-review-fields">
                    <label className="import-date-field">
                      Result date
                      <input type="date" value={importTestedAt} onChange={(event) => setImportTestedAt(event.target.value)} required />
                      <span>
                        {importSuggestedTestedAt
                          ? `Suggested from file: ${importSuggestedTestedAt}`
                          : 'Set the original lab result date for correct history graphs.'}
                      </span>
                    </label>
                    <label>
                      Family member
                      <select value={importMemberId} onChange={(event) => setImportMemberId(event.target.value)} required>
                        {(household?.members ?? []).map((member) => (
                          <option value={member.id} key={member.id}>{member.displayName}</option>
                        ))}
                      </select>
                    </label>
                    <label>
                      Lab
                      <input value={importLabName} onChange={(event) => setImportLabName(event.target.value)} placeholder="Diagnostyka" />
                    </label>
                    <label className="health-notes">
                      Notes
                      <input value={importNotes} onChange={(event) => setImportNotes(event.target.value)} />
                    </label>
                  </div>

                  <div className="marker-form-list">
                    <div className="marker-form-heading">
                      <h3>Markers from document</h3>
                      <button type="button" onClick={() => setImportMarkerRows((rows) => [...rows, createMarkerRow()])}>
                        Add marker
                      </button>
                    </div>
                    {importMarkerRows.map((row) => {
                      const warnings = markerWarnings(row)

                      return (
                      <div className={`marker-row ${warnings.length > 0 ? 'marker-row-warning' : ''} ${row.selected ? '' : 'marker-row-muted'}`} key={row.id}>
                        <label className="marker-select-field">
                          Import
                          <input
                            type="checkbox"
                            checked={row.selected}
                            onChange={(event) => updateImportMarkerRow(row.id, 'selected', event.target.checked)}
                          />
                        </label>
                        <label>
                          Marker
                          <input
                            value={row.markerName}
                            onChange={(event) => updateImportMarkerRow(row.id, 'markerName', event.target.value)}
                            list="health-marker-catalog"
                            placeholder="Hemoglobina"
                            required
                          />
                        </label>
                        <label>
                          Value
                          <input
                            type="number"
                            step="0.001"
                            value={row.value}
                            onChange={(event) => updateImportMarkerRow(row.id, 'value', event.target.value)}
                            required
                          />
                        </label>
                        <label>
                          Unit
                          <input
                            value={row.unit}
                            onChange={(event) => updateImportMarkerRow(row.id, 'unit', event.target.value)}
                            placeholder="g/dl"
                            required
                          />
                        </label>
                        <label>
                          Ref min
                          <input
                            type="number"
                            step="0.001"
                            value={row.referenceMin}
                            onChange={(event) => updateImportMarkerRow(row.id, 'referenceMin', event.target.value)}
                          />
                        </label>
                        <label>
                          Ref max
                          <input
                            type="number"
                            step="0.001"
                            value={row.referenceMax}
                            onChange={(event) => updateImportMarkerRow(row.id, 'referenceMax', event.target.value)}
                          />
                        </label>
                        <label>
                          Status
                          <select value={row.status} onChange={(event) => updateImportMarkerRow(row.id, 'status', event.target.value)}>
                            <option value="unknown">Unknown</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                          </select>
                        </label>
                        <label className="marker-note-field">
                          Marker notes
                          <input value={row.notes} onChange={(event) => updateImportMarkerRow(row.id, 'notes', event.target.value)} />
                        </label>
                        {warnings.length > 0 && (
                          <div className="marker-warning-list">
                            {warnings.map((warning) => <span key={warning}>{warning}</span>)}
                          </div>
                        )}
                        <button type="button" className="remove-marker" onClick={() => removeImportMarkerRow(row.id)}>
                          Remove
                        </button>
                      </div>
                      )
                    })}
                  </div>

                  <button type="submit" disabled={setupState === 'saving' || importMarkerRows.every((row) => !row.selected)}>
                    Save {importMarkerRows.filter((row) => row.selected).length} selected markers
                  </button>
                </form>
              )}
            </section>

            <div className="health-lists">
              <section>
                <h3>Latest blood tests</h3>
                <div className="blood-test-list">
                  {(healthOverview?.latestBloodTests ?? []).length > 0 ? (
                    healthOverview?.latestBloodTests.map((test) => (
                      <article className="blood-test-card" key={test.id}>
                        <div className="blood-test-card-heading">
                          <div>
                            <strong>{memberNameById(test.memberId)} · {test.testedAt}</strong>
                            <small>
                              {test.labName ?? 'Lab not set'}
                              {test.sourceDocumentId ? ` · from ${documentNameById(test.sourceDocumentId)}` : ''}
                              {test.notes ? ` · ${test.notes}` : ''}
                            </small>
                          </div>
                          <div className="blood-test-actions">
                            <button type="button" onClick={() => startEditBloodTest(test)}>
                              Edit
                            </button>
                            <button type="button" onClick={() => deleteBloodTest(test.id)}>
                              Delete
                            </button>
                          </div>
                        </div>
                        <div className="marker-chip-list">
                          {test.markers.map((marker) => (
                            <span className={`marker-chip status-${marker.status}`} key={marker.id}>
                              {marker.name}: {marker.value.toLocaleString('pl-PL')} {marker.unit}
                            </span>
                          ))}
                        </div>
                        {editingBloodTestId === test.id && (
                          <form className="inline-edit-form health-edit-form" onSubmit={updateBloodTest}>
                            <label>
                              Family member
                              <select value={editBloodTestMemberId} onChange={(event) => setEditBloodTestMemberId(event.target.value)} required>
                                {(household?.members ?? []).map((member) => (
                                  <option value={member.id} key={member.id}>{member.displayName}</option>
                                ))}
                              </select>
                            </label>
                            <label>
                              Test date
                              <input type="date" value={editBloodTestTestedAt} onChange={(event) => setEditBloodTestTestedAt(event.target.value)} required />
                            </label>
                            <label>
                              Lab
                              <input value={editBloodTestLabName} onChange={(event) => setEditBloodTestLabName(event.target.value)} />
                            </label>
                            <label className="health-notes">
                              Notes
                              <input value={editBloodTestNotes} onChange={(event) => setEditBloodTestNotes(event.target.value)} />
                            </label>
                            <div className="marker-form-list edit-marker-list">
                              <div className="marker-form-heading">
                                <h3>Markers</h3>
                                <button type="button" onClick={() => setEditMarkerRows((rows) => [...rows, createMarkerRow()])}>
                                  Add marker
                                </button>
                              </div>
                              {editMarkerRows.map((row) => {
                                const warnings = markerWarnings(row)

                                return (
                                  <div className={`marker-row ${warnings.length > 0 ? 'marker-row-warning' : ''}`} key={row.id}>
                                    <label>
                                      Marker
                                      <input
                                        value={row.markerName}
                                        onChange={(event) => updateEditMarkerRow(row.id, 'markerName', event.target.value)}
                                        list="health-marker-catalog"
                                        required
                                      />
                                    </label>
                                    <label>
                                      Value
                                      <input
                                        type="number"
                                        step="0.001"
                                        value={row.value}
                                        onChange={(event) => updateEditMarkerRow(row.id, 'value', event.target.value)}
                                        required
                                      />
                                    </label>
                                    <label>
                                      Unit
                                      <input value={row.unit} onChange={(event) => updateEditMarkerRow(row.id, 'unit', event.target.value)} required />
                                    </label>
                                    <label>
                                      Ref min
                                      <input type="number" step="0.001" value={row.referenceMin} onChange={(event) => updateEditMarkerRow(row.id, 'referenceMin', event.target.value)} />
                                    </label>
                                    <label>
                                      Ref max
                                      <input type="number" step="0.001" value={row.referenceMax} onChange={(event) => updateEditMarkerRow(row.id, 'referenceMax', event.target.value)} />
                                    </label>
                                    <label>
                                      Status
                                      <select value={row.status} onChange={(event) => updateEditMarkerRow(row.id, 'status', event.target.value)}>
                                        <option value="unknown">Unknown</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Low</option>
                                        <option value="high">High</option>
                                      </select>
                                    </label>
                                    <label className="marker-note-field">
                                      Marker notes
                                      <input value={row.notes} onChange={(event) => updateEditMarkerRow(row.id, 'notes', event.target.value)} />
                                    </label>
                                    {warnings.length > 0 && (
                                      <div className="marker-warning-list">
                                        {warnings.map((warning) => <span key={warning}>{warning}</span>)}
                                      </div>
                                    )}
                                    <button type="button" className="remove-marker" onClick={() => removeEditMarkerRow(row.id)}>
                                      Remove
                                    </button>
                                  </div>
                                )
                              })}
                            </div>
                            <div className="inline-edit-actions">
                              <button type="submit" disabled={setupState === 'saving'}>
                                Save changes
                              </button>
                              <button type="button" onClick={() => setEditingBloodTestId(null)}>
                                Cancel
                              </button>
                            </div>
                          </form>
                        )}
                      </article>
                    ))
                  ) : (
                    <p className="empty-state">No blood tests yet.</p>
                  )}
                </div>
              </section>

              <section>
                <h3>Needs attention</h3>
                <div className="money-list">
                  {(healthOverview?.outOfRangeMarkers ?? []).length > 0 ? (
                    healthOverview?.outOfRangeMarkers.map((marker) => (
                      <article className="health-marker-item" key={marker.id}>
                        <div>
                          <strong>{marker.name}</strong>
                          <small>{memberNameById(marker.memberId)} · {marker.testedAt}</small>
                        </div>
                        <b className={`status-pill status-${marker.status}`}>{marker.status}</b>
                        <span>{marker.value.toLocaleString('pl-PL')} {marker.unit}</span>
                      </article>
                    ))
                  ) : (
                    <p className="empty-state">No out-of-range markers recorded.</p>
                  )}
                </div>
              </section>
            </div>

            <section className="marker-history-panel">
              <div className="section-heading">
                <div>
                  <p className="eyebrow">History</p>
                  <h2>Marker timeline.</h2>
                </div>
                <label>
                  Marker
                  <select value={selectedMarkerName} onChange={(event) => setSelectedMarkerName(event.target.value)}>
                    <option value="">Choose marker</option>
                    {(healthOverview?.markerNames ?? []).map((markerName) => (
                      <option value={markerName} key={markerName}>{markerName}</option>
                    ))}
                  </select>
                </label>
              </div>

              {markerHistory.length > 0 && (
                <div className="marker-trend-grid">
                  <article>
                    <span>Latest</span>
                    <strong>{latestMarker?.value.toLocaleString('pl-PL')} {latestMarker?.unit}</strong>
                    <small>{latestMarker?.testedAt}</small>
                  </article>
                  <article>
                    <span>Change</span>
                    <strong>
                      {markerChange === null
                        ? 'First result'
                        : `${markerChange > 0 ? '+' : ''}${markerChange.toLocaleString('pl-PL')} ${latestMarker?.unit}`}
                    </strong>
                    <small>Compared with previous result</small>
                  </article>
                  <article>
                    <span>Reference</span>
                    <strong>
                      {latestReferenceMin !== null && latestReferenceMax !== null
                        ? `${latestReferenceMin.toLocaleString('pl-PL')} - ${latestReferenceMax.toLocaleString('pl-PL')}`
                        : 'Not set'}
                    </strong>
                    <small>{latestMarker?.status ?? 'unknown'}</small>
                  </article>
                </div>
              )}

              {markerHistory.length > 0 && (
                <div className="marker-chart" aria-label={`${selectedMarkerName} trend chart`}>
                  <svg viewBox={`0 0 ${markerChartWidth} ${markerChartHeight}`} role="img">
                    {referenceMinY !== null && referenceMaxY !== null && (
                      <rect
                        x="0"
                        y={Math.min(referenceMinY, referenceMaxY)}
                        width={markerChartWidth}
                        height={Math.abs(referenceMaxY - referenceMinY)}
                        className="reference-band"
                      />
                    )}
                    {[0, 0.25, 0.5, 0.75, 1].map((step) => (
                      <line
                        className="chart-grid-line"
                        x1="0"
                        x2={markerChartWidth}
                        y1={markerChartHeight * step}
                        y2={markerChartHeight * step}
                        key={step}
                      />
                    ))}
                    <polyline points={markerChartPoints} className="trend-line" />
                    {trendMarkers.map((marker, index) => (
                      <g key={marker.id}>
                        <circle
                          cx={markerChartX(index)}
                          cy={markerChartY(marker.value)}
                          r="7"
                          className={`trend-point status-${marker.status}`}
                        />
                        <text x={markerChartX(index)} y={markerChartY(marker.value) - 13}>
                          {marker.value.toLocaleString('pl-PL')}
                        </text>
                      </g>
                    ))}
                  </svg>
                  <div className="marker-chart-axis">
                    <span>{trendMarkers[0]?.testedAt}</span>
                    <span>{trendMarkers[trendMarkers.length - 1]?.testedAt}</span>
                  </div>
                </div>
              )}

              <div className="marker-history-list">
                {markerHistory.length > 0 ? (
                  markerHistory.map((marker) => (
                    <article key={marker.id}>
                      <span className={`status-dot status-${marker.status}`}></span>
                      <div>
                        <strong>{marker.testedAt}</strong>
                        <small>{memberNameById(marker.memberId)}</small>
                      </div>
                      <b>{marker.value.toLocaleString('pl-PL')} {marker.unit}</b>
                    </article>
                  ))
                ) : (
                  <p className="empty-state">Choose a marker to see its history.</p>
                )}
              </div>
            </section>

            {setupState === 'error' && <p className="form-error">Could not save health data.</p>}
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
