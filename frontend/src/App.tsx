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

type DocumentExtraction = {
  documentId: string
  status: 'extracted' | 'empty' | 'failed' | 'missing_file' | 'tool_missing' | 'unsupported'
  text: string
  message: string | null
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
  markerName: string
  value: string
  unit: string
  referenceMin: string
  referenceMax: string
  status: 'normal' | 'low' | 'high' | 'unknown'
  notes: string
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
const currentMonth = today.slice(0, 7)
const createMarkerRow = (): MarkerFormRow => ({
  id: crypto.randomUUID(),
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
  const [healthMemberFilterId, setHealthMemberFilterId] = useState('')
  const [openBloodTestCreator, setOpenBloodTestCreator] = useState(false)
  const [bloodTestMemberId, setBloodTestMemberId] = useState('')
  const [bloodTestTestedAt, setBloodTestTestedAt] = useState(today)
  const [bloodTestLabName, setBloodTestLabName] = useState('')
  const [bloodTestNotes, setBloodTestNotes] = useState('')
  const [markerRows, setMarkerRows] = useState<MarkerFormRow[]>([createMarkerRow()])
  const [selectedMarkerName, setSelectedMarkerName] = useState('')
  const [markerHistory, setMarkerHistory] = useState<BloodTestMarker[]>([])
  const [documentMemberId, setDocumentMemberId] = useState('')
  const [documentFile, setDocumentFile] = useState<File | null>(null)
  const [importDocument, setImportDocument] = useState<HealthDocument | null>(null)
  const [importMemberId, setImportMemberId] = useState('')
  const [importTestedAt, setImportTestedAt] = useState(today)
  const [importLabName, setImportLabName] = useState('')
  const [importNotes, setImportNotes] = useState('')
  const [importMarkerRows, setImportMarkerRows] = useState<MarkerFormRow[]>([createMarkerRow()])
  const [extractedText, setExtractedText] = useState('')
  const [extractionStatus, setExtractionStatus] = useState<DocumentExtraction['status'] | ''>('')
  const [extractionMessage, setExtractionMessage] = useState('')

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
    setMarkerHistory([])
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

    if (!expenseCategoryId && overview.categories[0]) {
      setExpenseCategoryId(overview.categories[0].id)
    }

    if (!billCategoryId && overview.categories[0]) {
      setBillCategoryId(overview.categories[0].id)
    }
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
      await loadExpenseOverview(household.id)
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
      await loadExpenseOverview(household.id)
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
      await loadExpenseOverview(household.id)
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
      await loadExpenseOverview(household.id)
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
      await loadExpenseOverview(household.id)
      setEditingBillId((current) => (current === billId ? null : current))
      setSetupState('idle')
    } catch {
      setSetupState('error')
    }
  }

  const updateMarkerRow = (rowId: string, field: keyof MarkerFormRow, value: string) => {
    setMarkerRows((rows) => rows.map((row) => (row.id === rowId ? { ...row, [field]: value } : row)))
  }

  const updateImportMarkerRow = (rowId: string, field: keyof MarkerFormRow, value: string) => {
    setImportMarkerRows((rows) => rows.map((row) => (row.id === rowId ? { ...row, [field]: value } : row)))
  }

  const removeMarkerRow = (rowId: string) => {
    setMarkerRows((rows) => (rows.length === 1 ? rows : rows.filter((row) => row.id !== rowId)))
  }

  const removeImportMarkerRow = (rowId: string) => {
    setImportMarkerRows((rows) => (rows.length === 1 ? rows : rows.filter((row) => row.id !== rowId)))
  }

  const startDocumentImport = (document: HealthDocument) => {
    setImportDocument(document)
    setImportMemberId(document.memberId ?? currentUser?.linkedMemberId ?? household?.members[0]?.id ?? '')
    setImportTestedAt(today)
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
          markers: markerRows.map((row) => ({
            markerName: row.markerName,
            value: Number(row.value),
            unit: row.unit,
            referenceMin: row.referenceMin ? Number(row.referenceMin) : null,
            referenceMax: row.referenceMax ? Number(row.referenceMax) : null,
            status: row.status,
            notes: row.notes || null,
          })),
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
          markers: importMarkerRows.map((row) => ({
            markerName: row.markerName,
            value: Number(row.value),
            unit: row.unit,
            referenceMin: row.referenceMin ? Number(row.referenceMin) : null,
            referenceMax: row.referenceMax ? Number(row.referenceMax) : null,
            status: row.status,
            notes: row.notes || null,
          })),
        }),
      })
      await loadHealthOverview(household.id)
      setImportDocument(null)
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
      if (extraction.markers.length > 0) {
        setImportMarkerRows(extraction.markers.map((marker) => ({
          id: crypto.randomUUID(),
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
      value: healthOverview?.markerNames.length ?? dashboard.summary.healthMarkersTracked,
      label: 'markers tracked',
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

            <div className="expense-lists">
              <section>
                <h3>Latest expenses</h3>
                <div className="money-list">
                  {(expenseOverview?.latestExpenses ?? []).length > 0 ? (
                    expenseOverview?.latestExpenses.map((expense) => (
                      <article className="money-item editable" key={expense.id}>
                        <span style={{ background: expense.category.color }}></span>
                        <div className="money-item-main">
                          <div>
                            <strong>{expense.description}</strong>
                            <small>{expense.category.name} · {memberNameById(expense.paidByMemberId)} · {expense.spentOn}</small>
                          </div>
                          <div className="row-actions">
                            <button type="button" onClick={() => startEditExpense(expense)}>Edit</button>
                            <button type="button" onClick={() => deleteExpense(expense.id)}>Delete</button>
                          </div>
                        </div>
                        <b>{expense.amount.toLocaleString('pl-PL')} PLN</b>
                        {editingExpenseId === expense.id && (
                          <form className="inline-edit-form" onSubmit={updateExpense}>
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
                      <article className="money-item editable" key={bill.id}>
                        <span style={{ background: bill.category.color }}></span>
                        <div className="money-item-main">
                          <div>
                            <strong>{bill.name}</strong>
                            <small>{bill.category.name} · due day {bill.dueDay} · {memberNameById(bill.paidByMemberId)}</small>
                          </div>
                          <div className="row-actions">
                            <button type="button" onClick={() => startEditBill(bill)}>Edit</button>
                            <button type="button" onClick={() => deleteRecurringBill(bill.id)}>Delete</button>
                          </div>
                        </div>
                        <b>{bill.amount.toLocaleString('pl-PL')} PLN</b>
                        {editingBillId === bill.id && (
                          <form className="inline-edit-form" onSubmit={updateRecurringBill}>
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
          <section className="health-panel page-card">
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
            </div>

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
                    <label>
                      Family member
                      <select value={importMemberId} onChange={(event) => setImportMemberId(event.target.value)} required>
                        {(household?.members ?? []).map((member) => (
                          <option value={member.id} key={member.id}>{member.displayName}</option>
                        ))}
                      </select>
                    </label>
                    <label>
                      Test date
                      <input type="date" value={importTestedAt} onChange={(event) => setImportTestedAt(event.target.value)} required />
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
                    {importMarkerRows.map((row) => (
                      <div className="marker-row" key={row.id}>
                        <label>
                          Marker
                          <input
                            value={row.markerName}
                            onChange={(event) => updateImportMarkerRow(row.id, 'markerName', event.target.value)}
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
                        <button type="button" className="remove-marker" onClick={() => removeImportMarkerRow(row.id)}>
                          Remove
                        </button>
                      </div>
                    ))}
                  </div>

                  <button type="submit" disabled={setupState === 'saving'}>
                    Save imported blood test
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
                        <div>
                          <strong>{memberNameById(test.memberId)} · {test.testedAt}</strong>
                          <small>
                            {test.labName ?? 'Lab not set'}
                            {test.sourceDocumentId ? ` · from ${documentNameById(test.sourceDocumentId)}` : ''}
                            {test.notes ? ` · ${test.notes}` : ''}
                          </small>
                        </div>
                        <div className="marker-chip-list">
                          {test.markers.map((marker) => (
                            <span className={`marker-chip status-${marker.status}`} key={marker.id}>
                              {marker.name}: {marker.value.toLocaleString('pl-PL')} {marker.unit}
                            </span>
                          ))}
                        </div>
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
