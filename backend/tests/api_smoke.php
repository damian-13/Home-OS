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
            'documents',
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

/**
 * @param array<string, string> $fields
 * @param array{name: string, path: string, mime: string}|null $file
 * @return array{status: int, body: mixed, raw: string, headers: list<string>}
 */
function apiMultipart(string $path, array $fields, ?array $file = null): array
{
    global $baseUrl, $cookieJar;

    $boundary = '----HomeOsSmoke'.bin2hex(random_bytes(8));
    $body = '';

    foreach ($fields as $name => $value) {
        $body .= sprintf("--%s\r\n", $boundary);
        $body .= sprintf("Content-Disposition: form-data; name=\"%s\"\r\n\r\n", $name);
        $body .= $value."\r\n";
    }

    if ($file !== null) {
        $contents = file_get_contents($file['path']);

        if ($contents === false) {
            fail(sprintf('Could not read multipart file %s.', $file['path']));
        }

        $body .= sprintf("--%s\r\n", $boundary);
        $body .= sprintf("Content-Disposition: form-data; name=\"file\"; filename=\"%s\"\r\n", $file['name']);
        $body .= sprintf("Content-Type: %s\r\n\r\n", $file['mime']);
        $body .= $contents."\r\n";
    }

    $body .= sprintf("--%s--\r\n", $boundary);

    $headers = [
        'Accept: application/json',
        sprintf('Content-Type: multipart/form-data; boundary=%s', $boundary),
    ];

    if ($cookieJar !== []) {
        $headers[] = 'Cookie: '.implode('; ', array_map(
            static fn (string $name, string $value): string => sprintf('%s=%s', $name, $value),
            array_keys($cookieJar),
            $cookieJar,
        ));
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
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

$memberId = (string) ($user['linkedMemberId'] ?? '');

$expiredDocument = apiMultipart(sprintf('/api/households/%s/documents', rawurlencode($householdId)), [
    'title' => 'Smoke expired insurance',
    'type' => 'insurance',
    'ownerMemberId' => $memberId,
    'issuedAt' => '',
    'expiresAt' => $yesterday,
    'tags' => 'smoke, insurance',
    'note' => 'Should appear as expired',
]);

assertTrue($expiredDocument['status'] === 201, 'Expired document create should return 201.');
assertTrue(is_string($expiredDocument['body']['id'] ?? null), 'Expired document create should return id.');

$expiringDocument = apiMultipart(sprintf('/api/households/%s/documents', rawurlencode($householdId)), [
    'title' => 'Smoke expiring warranty',
    'type' => 'warranty',
    'ownerMemberId' => '',
    'issuedAt' => '',
    'expiresAt' => $tomorrow,
    'tags' => 'smoke, warranty',
    'note' => 'Should appear as expiring soon',
]);

assertTrue($expiringDocument['status'] === 201, 'Expiring document create should return 201.');
assertTrue(is_string($expiringDocument['body']['id'] ?? null), 'Expiring document create should return id.');

$editableDocument = apiMultipart(sprintf('/api/households/%s/documents', rawurlencode($householdId)), [
    'title' => 'Smoke editable manual',
    'type' => 'manual',
    'ownerMemberId' => '',
    'issuedAt' => '',
    'expiresAt' => '',
    'tags' => 'smoke',
    'note' => 'Before edit',
]);

assertTrue($editableDocument['status'] === 201, 'Editable document create should return 201.');
assertTrue(is_string($editableDocument['body']['id'] ?? null), 'Editable document create should return id.');

$uploadedDocumentPath = sys_get_temp_dir().'/home-os-smoke-document.png';
file_put_contents($uploadedDocumentPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true));

$uploadedDocument = apiMultipart(sprintf('/api/households/%s/documents', rawurlencode($householdId)), [
    'title' => 'Smoke uploaded invoice',
    'type' => 'invoice',
    'ownerMemberId' => $memberId,
    'issuedAt' => (new DateTimeImmutable('today'))->format('Y-m-d'),
    'expiresAt' => '',
    'tags' => 'smoke, file',
    'note' => 'Has file',
], [
    'name' => 'smoke-document.png',
    'path' => $uploadedDocumentPath,
    'mime' => 'image/png',
]);

assertTrue($uploadedDocument['status'] === 201, sprintf('Uploaded document create should return 201, got %d: %s', $uploadedDocument['status'], $uploadedDocument['raw']));
assertTrue(is_string($uploadedDocument['body']['id'] ?? null), 'Uploaded document create should return id.');

$documentsList = apiRequest('GET', sprintf('/api/households/%s/documents', rawurlencode($householdId)));

assertTrue($documentsList['status'] === 200, 'Document list should return 200.');
assertTrue(is_array($documentsList['body']['documents'] ?? null), 'Document list should return documents.');
assertTrue(count($documentsList['body']['documents']) >= 4, 'Document list should include created documents.');

$documentsById = [];
foreach (($documentsList['body']['documents'] ?? []) as $document) {
    if (is_array($document) && isset($document['id'])) {
        $documentsById[(string) $document['id']] = $document;
    }
}

assertTrue(($documentsById[(string) $uploadedDocument['body']['id']]['originalName'] ?? null) === 'smoke-document.png', 'Uploaded document should keep original file name.');
assertTrue(is_string($documentsById[(string) $uploadedDocument['body']['id']]['downloadUrl'] ?? null), 'Uploaded document should expose download URL.');

$documentDownload = apiRequest('GET', (string) $documentsById[(string) $uploadedDocument['body']['id']]['downloadUrl']);

assertTrue($documentDownload['status'] === 200, 'Document download should return 200.');
assertTrue(strlen($documentDownload['raw']) > 0, 'Document download should return stored file content.');

$updateDocument = apiRequest('PATCH', sprintf(
    '/api/households/%s/documents/%s',
    rawurlencode($householdId),
    rawurlencode((string) $editableDocument['body']['id']),
), [
    'title' => 'Smoke edited manual',
    'type' => 'manual',
    'ownerMemberId' => null,
    'issuedAt' => null,
    'expiresAt' => null,
    'tags' => 'smoke, edited',
    'note' => 'After edit',
]);

assertTrue($updateDocument['status'] === 200, 'Document metadata update should return 200.');

$documentsAfterEdit = apiRequest('GET', sprintf('/api/households/%s/documents', rawurlencode($householdId)));
$editedDocument = null;
foreach (($documentsAfterEdit['body']['documents'] ?? []) as $document) {
    if (is_array($document) && ($document['id'] ?? null) === $editableDocument['body']['id']) {
        $editedDocument = $document;
        break;
    }
}

assertTrue(($editedDocument['title'] ?? null) === 'Smoke edited manual', 'Document update should change title.');
assertTrue(($editedDocument['tags'] ?? null) === 'smoke, edited', 'Document update should change tags.');

$deleteDocument = apiRequest('DELETE', sprintf(
    '/api/households/%s/documents/%s',
    rawurlencode($householdId),
    rawurlencode((string) $editableDocument['body']['id']),
));

assertTrue($deleteDocument['status'] === 204, 'Document delete should return 204.');

$documentsAfterDelete = apiRequest('GET', sprintf('/api/households/%s/documents', rawurlencode($householdId)));
$deletedStillVisible = false;
foreach (($documentsAfterDelete['body']['documents'] ?? []) as $document) {
    if (is_array($document) && ($document['id'] ?? null) === $editableDocument['body']['id']) {
        $deletedStillVisible = true;
        break;
    }
}

assertTrue(!$deletedStillVisible, 'Deleted document should not be listed.');

$dashboardWithHome = apiRequest('GET', '/api/dashboard');
$homeAttention = array_values(array_filter(
    $dashboardWithHome['body']['attention'] ?? [],
    static fn (array $item): bool => ($item['area'] ?? null) === 'home',
));
$reminderAttention = array_values(array_filter(
    $dashboardWithHome['body']['attention'] ?? [],
    static fn (array $item): bool => ($item['area'] ?? null) === 'reminders',
));
$documentAttention = array_values(array_filter(
    $dashboardWithHome['body']['attention'] ?? [],
    static fn (array $item): bool => ($item['area'] ?? null) === 'documents',
));

assertTrue(($dashboardWithHome['body']['summary']['homeTasksDue'] ?? 0) >= 2, 'Dashboard should count due home tasks.');
assertTrue(count($homeAttention) >= 2, 'Dashboard should include home overdue and upcoming attention items.');
assertTrue(in_array('home', array_column($homeAttention, 'targetPage'), true), 'Home attention should navigate to Home page.');
assertTrue(($dashboardWithHome['body']['summary']['remindersDue'] ?? 0) >= 3, 'Dashboard should count due reminders.');
assertTrue(count($reminderAttention) >= 3, 'Dashboard should include overdue, today, and upcoming reminder attention items.');
assertTrue(in_array('reminders', array_column($reminderAttention, 'targetPage'), true), 'Reminder attention should navigate to Reminders page.');
assertTrue(($dashboardWithHome['body']['summary']['documentsStored'] ?? 0) >= 3, 'Dashboard should count stored documents.');
assertTrue(count($documentAttention) >= 2, 'Dashboard should include expired and expiring document attention items.');
assertTrue(in_array('documents', array_column($documentAttention, 'targetPage'), true), 'Document attention should navigate to Documents page.');

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
assertTrue(in_array('documents', $inboxSources, true), 'Inbox should include expired and expiring document items.');

$search = apiRequest('GET', sprintf('/api/households/%s/search?q=Smoke', rawurlencode($householdId)));

assertTrue($search['status'] === 200, 'Search should return 200 for household member.');
assertTrue(is_array($search['body']['results'] ?? null), 'Search should include results list.');
assertTrue(count($search['body']['results']) >= 3, 'Search should return records from multiple modules.');

$searchSources = array_values(array_unique(array_map(
    static fn (array $item): string => (string) ($item['sourceModule'] ?? ''),
    $search['body']['results'],
)));

assertTrue(count($searchSources) >= 3, 'Search should include at least three source modules.');
assertTrue(in_array('expenses', $searchSources, true), 'Search should include expense results.');
assertTrue(in_array('health', $searchSources, true), 'Search should include health results.');
assertTrue(in_array('documents', $searchSources, true), 'Search should include document results.');
assertTrue(count(array_filter(
    $search['body']['results'],
    static fn (array $item): bool => is_string($item['targetUrl'] ?? null) && str_starts_with((string) $item['targetUrl'], '#'),
)) === count($search['body']['results']), 'Search results should include source navigation targets.');

$forbiddenSearch = apiRequest('GET', sprintf('/api/households/%s/search?q=Smoke', rawurlencode(sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6))))));
assertTrue($forbiddenSearch['status'] === 403, 'Search should respect household access boundaries.');

$timeline = apiRequest('GET', sprintf('/api/households/%s/timeline', rawurlencode($householdId)));

assertTrue($timeline['status'] === 200, 'Timeline should return 200 for household member.');
assertTrue(is_array($timeline['body']['items'] ?? null), 'Timeline should include items list.');
assertTrue(count($timeline['body']['items']) >= 3, 'Timeline should return events from multiple modules.');

$timelineSources = array_values(array_unique(array_map(
    static fn (array $item): string => (string) ($item['sourceModule'] ?? ''),
    $timeline['body']['items'],
)));

assertTrue(count($timelineSources) >= 3, 'Timeline should include at least three source modules.');
assertTrue(in_array('expenses', $timelineSources, true), 'Timeline should include expense events.');
assertTrue(in_array('health', $timelineSources, true), 'Timeline should include health events.');
assertTrue(in_array('documents', $timelineSources, true), 'Timeline should include document events.');
assertTrue(count(array_filter(
    $timeline['body']['items'],
    static fn (array $item): bool => is_string($item['targetUrl'] ?? null) && str_starts_with((string) $item['targetUrl'], '#') && is_string($item['occurredAt'] ?? null),
)) === count($timeline['body']['items']), 'Timeline items should include date and source navigation targets.');

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
