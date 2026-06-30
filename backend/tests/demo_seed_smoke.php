<?php

declare(strict_types=1);

$databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? '');
$parts = parse_url($databaseUrl);

if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
    fwrite(STDERR, "FAIL: DATABASE_URL must point to PostgreSQL for demo seed smoke.\n");
    exit(1);
}

$host = (string) ($parts['host'] ?? 'database');
$port = (int) ($parts['port'] ?? 5432);
$database = ltrim((string) ($parts['path'] ?? ''), '/');
$user = rawurldecode((string) ($parts['user'] ?? ''));
$password = rawurldecode((string) ($parts['pass'] ?? ''));
$prefix = 'homeos-demo-smoke';
$householdName = 'Demo Smoke Household';
$checks = 0;

function failSeedSmoke(string $message): never
{
    fwrite(STDERR, sprintf("FAIL: %s\n", $message));
    exit(1);
}

function assertSeedSmoke(bool $condition, string $message): void
{
    global $checks;
    ++$checks;

    if (!$condition) {
        failSeedSmoke($message);
    }
}

function runSeedCommand(string $arguments): void
{
    $command = sprintf('php bin/console homeos:seed-demo-data %s 2>&1', $arguments);
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        failSeedSmoke(sprintf("Command failed:\n%s", implode("\n", $output)));
    }
}

$pdo = new PDO(sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database), $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

runSeedCommand(sprintf('--reset-demo --household-name=%s --months=3 --email-prefix=%s', escapeshellarg($householdName), escapeshellarg($prefix)));

$householdId = $pdo->prepare("SELECT household_id FROM user_accounts WHERE email = :email");
$householdId->execute(['email' => $prefix.'-damian@example.test']);
$householdId = $householdId->fetchColumn();

assertSeedSmoke(is_string($householdId) && $householdId !== '', 'Demo seed should create Damian demo user and household.');

$count = static function (string $table) use ($pdo, $householdId): int {
    $statement = $pdo->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE household_id = :householdId', $table));
    $statement->execute(['householdId' => $householdId]);

    return (int) $statement->fetchColumn();
};

assertSeedSmoke($count('expenses') >= 40, 'Demo seed should create finance expenses.');
assertSeedSmoke($count('income_entries') >= 6, 'Demo seed should create income entries.');
assertSeedSmoke($count('blood_tests') >= 5, 'Demo seed should create health blood tests.');
assertSeedSmoke($count('home_maintenance_tasks') >= 6, 'Demo seed should create home maintenance tasks.');
assertSeedSmoke($count('reminders') >= 5, 'Demo seed should create reminders.');
assertSeedSmoke($count('documents') >= 8, 'Demo seed should create documents.');

$financeReview = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE household_id = :householdId AND review_status = 'needs_review'");
$financeReview->execute(['householdId' => $householdId]);
assertSeedSmoke((int) $financeReview->fetchColumn() >= 4, 'Demo seed should create imported finance rows needing review.');

$overdueHome = $pdo->prepare("SELECT COUNT(*) FROM home_maintenance_tasks WHERE household_id = :householdId AND status = 'active' AND next_due_at < CURRENT_DATE");
$overdueHome->execute(['householdId' => $householdId]);
assertSeedSmoke((int) $overdueHome->fetchColumn() >= 1, 'Demo seed should create overdue home tasks.');

$expiringDocs = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE household_id = :householdId AND expires_at BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'");
$expiringDocs->execute(['householdId' => $householdId]);
assertSeedSmoke((int) $expiringDocs->fetchColumn() >= 1, 'Demo seed should create expiring documents.');

runSeedCommand(sprintf('--reset-demo --household-name=%s --months=1 --email-prefix=%s', escapeshellarg($householdName), escapeshellarg($prefix)));

$remaining = $pdo->prepare('SELECT COUNT(*) FROM user_accounts WHERE email LIKE :email');
$remaining->execute(['email' => $prefix.'-%@example.test']);
assertSeedSmoke((int) $remaining->fetchColumn() === 3, 'Reset plus seed should recreate only the demo smoke users.');

runSeedCommand(sprintf('--reset-demo --reset-only --household-name=%s --months=1 --email-prefix=%s', escapeshellarg($householdName), escapeshellarg($prefix)));

$remainingHouseholds = $pdo->prepare('SELECT COUNT(*) FROM user_accounts WHERE email LIKE :email');
$remainingHouseholds->execute(['email' => $prefix.'-%@example.test']);
assertSeedSmoke((int) $remainingHouseholds->fetchColumn() === 0, '--reset-demo --reset-only should remove only selected demo users.');

echo sprintf("Demo seed smoke passed: %d checks.\n", $checks);
