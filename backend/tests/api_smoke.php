<?php

declare(strict_types=1);

$baseUrl = rtrim((string) ($_SERVER['HOME_OS_API_BASE_URL'] ?? 'http://127.0.0.1:8000'), '/');
$password = 'password123';
$runId = strtolower(bin2hex(random_bytes(4)));
$email = sprintf('smoke-%s@example.test', $runId);
$cookieJar = [];
$checks = 0;
$createdHouseholdId = null;
$createdEmail = $email;

register_shutdown_function(static function () use (&$createdHouseholdId, &$createdEmail): void {
    if (!is_string($createdHouseholdId) || $createdHouseholdId === '') {
        return;
    }

    $databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? '');
    $parts = parse_url($databaseUrl);

    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
        fwrite(STDERR, "WARN: could not cleanup smoke test data because DATABASE_URL is not PostgreSQL.\n");
        return;
    }

    $host = (string) ($parts['host'] ?? 'database');
    $port = (int) ($parts['port'] ?? 5432);
    $database = ltrim((string) ($parts['path'] ?? ''), '/');
    $user = rawurldecode((string) ($parts['user'] ?? ''));
    $password = rawurldecode((string) ($parts['pass'] ?? ''));

    try {
        $pdo = new PDO(sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database), $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->beginTransaction();

        foreach ([
            'finance_review_batches',
            'finance_review_rules',
            'recurring_bill_payments',
            'expense_budgets',
            'expenses',
            'recurring_bills',
            'income_entries',
            'income_sources',
            'expense_categories',
            'health_documents',
            'blood_tests',
            'home_maintenance_tasks',
            'reminders',
        ] as $table) {
            $statement = $pdo->prepare(sprintf('DELETE FROM %s WHERE household_id = :householdId', $table));
            $statement->execute(['householdId' => $createdHouseholdId]);
        }

        $statement = $pdo->prepare('DELETE FROM user_accounts WHERE email = :email');
        $statement->execute(['email' => $createdEmail]);

        $statement = $pdo->prepare('DELETE FROM households WHERE id = :householdId');
        $statement->execute(['householdId' => $createdHouseholdId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        fwrite(STDERR, sprintf("WARN: could not cleanup smoke test data: %s\n", $exception->getMessage()));
    }
});

function fail(string $message): never
{
    fwrite(STDERR, sprintf("FAIL: %s\n", $message));
    exit(1);
}

function assertTrue(bool $condition, string $message): void
{
    global $checks;
    ++$checks;

    if (!$condition) {
        fail($message);
    }
}

/**
 * @param array<string, mixed>|null $payload
 * @return array{status: int, body: mixed, raw: string, headers: list<string>}
 */
function apiRequest(string $method, string $path, ?array $payload = null): array
{
    global $baseUrl, $cookieJar;

    $headers = ['Accept: application/json'];
    $body = null;

    if ($payload !== null) {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers[] = 'Content-Type: application/json';
    }

    if ($cookieJar !== []) {
        $headers[] = 'Cookie: '.implode('; ', array_map(
            static fn (string $name, string $value): string => sprintf('%s=%s', $name, $value),
            array_keys($cookieJar),
            $cookieJar,
        ));
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);

    $raw = @file_get_contents($baseUrl.$path, false, $context);

    if ($raw === false) {
        fail(sprintf('Could not connect to %s. Is the backend container running?', $baseUrl));
    }

    $responseHeaders = $http_response_header ?? [];
    $status = 0;

    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
        }

        if (preg_match('/^Set-Cookie:\s*([^=;]+)=([^;]*)/i', $header, $matches)) {
            $cookieJar[$matches[1]] = $matches[2];
        }
    }

    $decoded = json_decode($raw, true);

    return [
        'status' => $status,
        'body' => json_last_error() === JSON_ERROR_NONE ? $decoded : null,
        'raw' => $raw,
        'headers' => $responseHeaders,
    ];
}

$register = apiRequest('POST', '/api/auth/register', [
    'email' => $email,
    'password' => $password,
    'displayName' => 'Smoke Test',
    'householdName' => 'Smoke Test Household',
]);

assertTrue($register['status'] === 201, 'Register should create a user account.');
assertTrue(is_array($register['body']), 'Register should return JSON.');
assertTrue(is_string($register['body']['householdId'] ?? null), 'Register should return a household id.');

$createdHouseholdId = (string) $register['body']['householdId'];

$login = apiRequest('POST', '/api/auth/login', [
    'email' => $email,
    'password' => $password,
]);

assertTrue($login['status'] >= 200 && $login['status'] < 300, 'Login should succeed.');

$me = apiRequest('GET', '/api/auth/me');

assertTrue($me['status'] === 200, 'Me endpoint should return 200 after login.');
assertTrue(is_array($me['body']['user'] ?? null), 'Me endpoint should return the current user.');

$user = $me['body']['user'];
$householdId = (string) $user['householdId'];

assertTrue($householdId === $register['body']['householdId'], 'Current user household should match registered household.');

$dashboard = apiRequest('GET', '/api/dashboard');

assertTrue($dashboard['status'] === 200, 'Dashboard should return 200 for authenticated user.');
assertTrue(($dashboard['body']['app'] ?? null) === 'Home OS', 'Dashboard should identify Home OS.');
assertTrue(is_array($dashboard['body']['summary'] ?? null), 'Dashboard should include summary data.');
assertTrue(array_key_exists('monthlySpend', $dashboard['body']['summary']), 'Dashboard summary should include monthly spend.');
assertTrue(is_array($dashboard['body']['attention'] ?? null), 'Dashboard should include attention items list.');

$expenses = apiRequest('GET', sprintf('/api/households/%s/expenses/overview', rawurlencode($householdId)));

assertTrue($expenses['status'] === 200, 'Expense overview should return 200 for household member.');
assertTrue(($expenses['body']['currency'] ?? null) === 'PLN', 'Expense overview should use PLN.');
assertTrue(is_array($expenses['body']['categories'] ?? null), 'Expense overview should include categories.');
assertTrue(count($expenses['body']['categories']) > 0, 'Expense overview should create default categories.');
assertTrue(is_array($expenses['body']['activeFilters'] ?? null), 'Expense overview should include active filters.');

$categoryId = (string) $expenses['body']['categories'][0]['id'];
$reviewExpense = apiRequest('POST', sprintf('/api/households/%s/expenses', rawurlencode($householdId)), [
    'categoryId' => $categoryId,
    'description' => 'Smoke inbox uncertain expense',
    'amount' => 12.34,
    'spentOn' => (new DateTimeImmutable())->format('Y-m-d'),
    'paidByMemberId' => null,
]);

assertTrue($reviewExpense['status'] === 201, 'Smoke review expense create should return 201.');
assertTrue(is_string($reviewExpense['body']['id'] ?? null), 'Smoke review expense create should return id.');

$markExpenseForReview = apiRequest('PATCH', sprintf(
    '/api/households/%s/expenses/%s',
    rawurlencode($householdId),
    rawurlencode((string) $reviewExpense['body']['id']),
), [
    'categoryId' => $categoryId,
    'description' => 'Smoke inbox uncertain expense',
    'amount' => 12.34,
    'spentOn' => (new DateTimeImmutable())->format('Y-m-d'),
    'paidByMemberId' => null,
    'reviewStatus' => 'needs_review',
    'reviewReason' => 'Smoke test item needs review',
]);

assertTrue($markExpenseForReview['status'] === 200, 'Smoke review expense update should return 200.');

$yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
$tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

$oneTimeTask = apiRequest('POST', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)), [
    'title' => 'Smoke clean gutters',
    'area' => 'Roof',
    'nextDueAt' => $yesterday,
    'recurrenceType' => 'none',
    'priority' => 'high',
    'notes' => 'Created by smoke test',
]);

assertTrue($oneTimeTask['status'] === 201, 'Home maintenance task create should return 201.');
assertTrue(is_string($oneTimeTask['body']['id'] ?? null), 'Home maintenance task create should return id.');

$recurringTask = apiRequest('POST', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)), [
    'title' => 'Smoke replace filter',
    'area' => 'Heating',
    'nextDueAt' => $yesterday,
    'recurrenceType' => 'weekly',
    'priority' => 'normal',
    'notes' => null,
]);

assertTrue($recurringTask['status'] === 201, 'Recurring home task create should return 201.');
assertTrue(is_string($recurringTask['body']['id'] ?? null), 'Recurring home task create should return id.');

$dashboardOverdueTask = apiRequest('POST', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)), [
    'title' => 'Smoke water meter reading',
    'area' => 'Utilities',
    'nextDueAt' => $yesterday,
    'recurrenceType' => 'monthly',
    'priority' => 'high',
    'notes' => null,
]);

assertTrue($dashboardOverdueTask['status'] === 201, 'Dashboard overdue home task create should return 201.');

$upcomingTask = apiRequest('POST', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)), [
    'title' => 'Smoke test boiler pressure',
    'area' => 'Heating',
    'nextDueAt' => $tomorrow,
    'recurrenceType' => 'none',
    'priority' => 'normal',
    'notes' => null,
]);

assertTrue($upcomingTask['status'] === 201, 'Upcoming home task create should return 201.');

$homeList = apiRequest('GET', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)));

assertTrue($homeList['status'] === 200, 'Home maintenance task list should return 200.');
assertTrue(is_array($homeList['body']['tasks'] ?? null), 'Home maintenance task list should return tasks.');
assertTrue(count($homeList['body']['tasks']) >= 4, 'Home maintenance task list should include created tasks.');

$completeOneTime = apiRequest('POST', sprintf(
    '/api/households/%s/home/maintenance-tasks/%s/complete',
    rawurlencode($householdId),
    rawurlencode((string) $oneTimeTask['body']['id']),
));

assertTrue($completeOneTime['status'] === 200, 'Completing one-time home task should return 200.');

$completeRecurring = apiRequest('POST', sprintf(
    '/api/households/%s/home/maintenance-tasks/%s/complete',
    rawurlencode($householdId),
    rawurlencode((string) $recurringTask['body']['id']),
));

assertTrue($completeRecurring['status'] === 200, 'Completing recurring home task should return 200.');

$homeAfterCompletion = apiRequest('GET', sprintf('/api/households/%s/home/maintenance-tasks', rawurlencode($householdId)));
$tasksById = [];
foreach (($homeAfterCompletion['body']['tasks'] ?? []) as $task) {
    if (is_array($task) && isset($task['id'])) {
        $tasksById[(string) $task['id']] = $task;
    }
}

$completedOneTime = $tasksById[(string) $oneTimeTask['body']['id']] ?? null;
$completedRecurring = $tasksById[(string) $recurringTask['body']['id']] ?? null;

assertTrue(($completedOneTime['status'] ?? null) === 'completed', 'Completing one-time task should mark it completed.');
assertTrue(is_string($completedOneTime['completedAt'] ?? null), 'Completing one-time task should set completedAt.');
assertTrue(($completedRecurring['status'] ?? null) === 'active', 'Completing recurring task should keep it active.');
assertTrue(($completedRecurring['nextDueAt'] ?? '') > $yesterday, 'Completing recurring task should advance next due date.');
assertTrue(is_string($completedRecurring['completedAt'] ?? null), 'Completing recurring task should set completedAt.');

$oneTimeReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke one-time reminder',
    'note' => 'Complete once',
    'dueAt' => $yesterday,
    'recurrenceType' => 'none',
    'priority' => 'high',
]);

assertTrue($oneTimeReminder['status'] === 201, 'One-time reminder create should return 201.');
assertTrue(is_string($oneTimeReminder['body']['id'] ?? null), 'One-time reminder create should return id.');

$recurringCompleteReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke recurring complete reminder',
    'note' => null,
    'dueAt' => $yesterday,
    'recurrenceType' => 'weekly',
    'priority' => 'normal',
]);

assertTrue($recurringCompleteReminder['status'] === 201, 'Recurring complete reminder create should return 201.');

$recurringSkipReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke recurring skip reminder',
    'note' => null,
    'dueAt' => $yesterday,
    'recurrenceType' => 'monthly',
    'priority' => 'normal',
]);

assertTrue($recurringSkipReminder['status'] === 201, 'Recurring skip reminder create should return 201.');

$dashboardOverdueReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke overdue reminder for dashboard',
    'note' => 'Should appear in Dashboard and Inbox',
    'dueAt' => $yesterday,
    'recurrenceType' => 'none',
    'priority' => 'high',
]);

assertTrue($dashboardOverdueReminder['status'] === 201, 'Dashboard overdue reminder create should return 201.');

$dashboardTodayReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke today reminder for dashboard',
    'note' => 'Due today',
    'dueAt' => (new DateTimeImmutable('today'))->format('Y-m-d'),
    'recurrenceType' => 'none',
    'priority' => 'normal',
]);

assertTrue($dashboardTodayReminder['status'] === 201, 'Dashboard today reminder create should return 201.');

$dashboardUpcomingReminder = apiRequest('POST', sprintf('/api/households/%s/reminders', rawurlencode($householdId)), [
    'title' => 'Smoke upcoming reminder for dashboard',
    'note' => 'Due soon',
    'dueAt' => $tomorrow,
    'recurrenceType' => 'none',
    'priority' => 'low',
]);

assertTrue($dashboardUpcomingReminder['status'] === 201, 'Dashboard upcoming reminder create should return 201.');

$reminderList = apiRequest('GET', sprintf('/api/households/%s/reminders', rawurlencode($householdId)));

assertTrue($reminderList['status'] === 200, 'Reminder list should return 200.');
assertTrue(is_array($reminderList['body']['reminders'] ?? null), 'Reminder list should return reminders.');
assertTrue(count($reminderList['body']['reminders']) >= 6, 'Reminder list should include created reminders.');

$completeOneTimeReminder = apiRequest('POST', sprintf(
    '/api/households/%s/reminders/%s/complete',
    rawurlencode($householdId),
    rawurlencode((string) $oneTimeReminder['body']['id']),
));

assertTrue($completeOneTimeReminder['status'] === 200, 'Completing one-time reminder should return 200.');

$completeRecurringReminder = apiRequest('POST', sprintf(
    '/api/households/%s/reminders/%s/complete',
    rawurlencode($householdId),
    rawurlencode((string) $recurringCompleteReminder['body']['id']),
));

assertTrue($completeRecurringReminder['status'] === 200, 'Completing recurring reminder should return 200.');

$skipRecurringReminder = apiRequest('POST', sprintf(
    '/api/households/%s/reminders/%s/skip',
    rawurlencode($householdId),
    rawurlencode((string) $recurringSkipReminder['body']['id']),
));

assertTrue($skipRecurringReminder['status'] === 200, 'Skipping recurring reminder should return 200.');

$remindersAfterActions = apiRequest('GET', sprintf('/api/households/%s/reminders', rawurlencode($householdId)));
$remindersById = [];
foreach (($remindersAfterActions['body']['reminders'] ?? []) as $reminder) {
    if (is_array($reminder) && isset($reminder['id'])) {
        $remindersById[(string) $reminder['id']] = $reminder;
    }
}

$completedOneTimeReminder = $remindersById[(string) $oneTimeReminder['body']['id']] ?? null;
$completedRecurringReminder = $remindersById[(string) $recurringCompleteReminder['body']['id']] ?? null;
$skippedRecurringReminder = $remindersById[(string) $recurringSkipReminder['body']['id']] ?? null;

assertTrue(($completedOneTimeReminder['status'] ?? null) === 'completed', 'Completing one-time reminder should mark it completed.');
assertTrue(is_string($completedOneTimeReminder['completedAt'] ?? null), 'Completing one-time reminder should set completedAt.');
assertTrue(($completedRecurringReminder['status'] ?? null) === 'pending', 'Completing recurring reminder should keep it pending.');
assertTrue(($completedRecurringReminder['dueAt'] ?? '') > $yesterday, 'Completing recurring reminder should advance due date.');
assertTrue(is_string($completedRecurringReminder['completedAt'] ?? null), 'Completing recurring reminder should set completedAt.');
assertTrue(($skippedRecurringReminder['status'] ?? null) === 'pending', 'Skipping recurring reminder should keep it pending.');
assertTrue(($skippedRecurringReminder['dueAt'] ?? '') > $yesterday, 'Skipping recurring reminder should advance due date.');
assertTrue(is_string($skippedRecurringReminder['skippedAt'] ?? null), 'Skipping recurring reminder should set skippedAt.');

$dashboardWithHome = apiRequest('GET', '/api/dashboard');
$homeAttention = array_values(array_filter(
    $dashboardWithHome['body']['attention'] ?? [],
    static fn (array $item): bool => ($item['area'] ?? null) === 'home',
));
$reminderAttention = array_values(array_filter(
    $dashboardWithHome['body']['attention'] ?? [],
    static fn (array $item): bool => ($item['area'] ?? null) === 'reminders',
));

assertTrue(($dashboardWithHome['body']['summary']['homeTasksDue'] ?? 0) >= 2, 'Dashboard should count due home tasks.');
assertTrue(count($homeAttention) >= 2, 'Dashboard should include home overdue and upcoming attention items.');
assertTrue(in_array('home', array_column($homeAttention, 'targetPage'), true), 'Home attention should navigate to Home page.');
assertTrue(($dashboardWithHome['body']['summary']['remindersDue'] ?? 0) >= 3, 'Dashboard should count due reminders.');
assertTrue(count($reminderAttention) >= 3, 'Dashboard should include overdue, today, and upcoming reminder attention items.');
assertTrue(in_array('reminders', array_column($reminderAttention, 'targetPage'), true), 'Reminder attention should navigate to Reminders page.');

$memberId = (string) ($user['linkedMemberId'] ?? '');
$bloodTest = apiRequest('POST', sprintf('/api/households/%s/health/blood-tests', rawurlencode($householdId)), [
    'memberId' => $memberId,
    'testedAt' => (new DateTimeImmutable())->format('Y-m-d'),
    'labName' => 'Smoke Lab',
    'notes' => null,
    'markers' => [[
        'markerName' => 'Smoke LDL',
        'value' => 220,
        'unit' => 'mg/dl',
        'referenceMin' => 0,
        'referenceMax' => 100,
        'status' => 'high',
        'notes' => null,
    ]],
]);

assertTrue($bloodTest['status'] === 201, 'Smoke out-of-range blood test create should return 201.');

$inbox = apiRequest('GET', sprintf('/api/households/%s/inbox', rawurlencode($householdId)));

assertTrue($inbox['status'] === 200, 'Inbox should return 200 for household member.');
assertTrue(is_array($inbox['body']['items'] ?? null), 'Inbox should include items list.');
assertTrue(is_array($inbox['body']['summary'] ?? null), 'Inbox should include summary.');
assertTrue(($inbox['body']['summary']['total'] ?? 0) >= 3, 'Inbox should aggregate multiple review items.');

$inboxSources = array_values(array_unique(array_map(
    static fn (array $item): string => (string) ($item['sourceModule'] ?? ''),
    $inbox['body']['items'],
)));

assertTrue(in_array('expenses', $inboxSources, true), 'Inbox should include expense review items.');
assertTrue(in_array('health', $inboxSources, true), 'Inbox should include health review items.');
assertTrue(in_array('home', $inboxSources, true), 'Inbox should include home maintenance items.');
assertTrue(in_array('reminders', $inboxSources, true), 'Inbox should include due reminder items.');

$dashboardWithInbox = apiRequest('GET', '/api/dashboard');

assertTrue(array_key_exists('inboxItemsDue', $dashboardWithInbox['body']['summary'] ?? []), 'Dashboard summary should include inbox item count.');
assertTrue(($dashboardWithInbox['body']['summary']['inboxItemsDue'] ?? 0) >= 3, 'Dashboard inbox count should reflect review items.');
assertTrue(($dashboardWithInbox['body']['summary']['inboxHighestSeverity'] ?? null) !== null, 'Dashboard summary should include inbox highest severity.');

$health = apiRequest('GET', sprintf('/api/households/%s/health/overview', rawurlencode($householdId)));

assertTrue($health['status'] === 200, 'Health overview should return 200 for household member.');
assertTrue(is_array($health['body']['latestBloodTests'] ?? null), 'Health overview should include latest blood tests list.');
assertTrue(is_array($health['body']['outOfRangeMarkers'] ?? null), 'Health overview should include out-of-range marker list.');
assertTrue(is_array($health['body']['markerNames'] ?? null), 'Health overview should include marker names list.');
assertTrue(is_array($health['body']['markerCatalog'] ?? null), 'Health overview should include marker catalog.');

printf("OK: backend API smoke tests passed (%d checks).\n", $checks);
