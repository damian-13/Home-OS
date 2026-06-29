import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const root = resolve(import.meta.dirname, '..')

const checks = [
  {
    file: 'src/features/dashboard/DashboardPage.tsx',
    contains: ['What should I do today?', 'Quick Capture', 'Recent Activity'],
    label: 'Dashboard decision center',
  },
  {
    file: 'src/features/quick-actions/QuickActionMenu.tsx',
    contains: ['Quick actions', 'Open quick actions'],
    label: 'Global quick action menu',
  },
  {
    file: 'src/features/inbox/InboxPage.tsx',
    contains: ['Source', 'Severity', 'Everything important looks calm for today.'],
    label: 'Inbox filters',
  },
  {
    file: 'src/App.tsx',
    contains: ['Expense', 'Health Result', 'setExpenseSection(\'import-review\')', 'documentEmptyCopy'],
    label: 'Quick actions, expenses import, and setup empty states',
  },
  {
    file: 'src/features/search/SearchPage.tsx',
    contains: ['Search across Home OS', 'Search expenses, health markers, home tasks, reminders, and documents from one place.'],
    label: 'Search page',
  },
  {
    file: 'src/features/timeline/TimelinePage.tsx',
    contains: ['Important household timeline', 'Routine low-value transactions are grouped'],
    label: 'Timeline page',
  },
]

const failures = []

for (const check of checks) {
  const source = readFileSync(resolve(root, check.file), 'utf8')

  for (const expected of check.contains) {
    if (!source.includes(expected)) {
      failures.push(`${check.label}: missing "${expected}" in ${check.file}`)
    }
  }
}

if (failures.length > 0) {
  console.error(failures.join('\n'))
  process.exit(1)
}

console.log(`Frontend smoke passed: ${checks.length} daily UX surfaces checked.`)
