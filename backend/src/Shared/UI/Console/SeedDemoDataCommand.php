<?php

namespace App\Shared\UI\Console;

use App\Documents\Domain\Model\Document;
use App\Expenses\Application\DefaultExpenseCategories;
use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Model\ExpenseBudget;
use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Model\FinanceReviewRule;
use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Model\IncomeSource;
use App\Expenses\Domain\Model\RecurringBill;
use App\Expenses\Domain\Model\RecurringBillPayment;
use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\HealthDocument;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Household\Domain\Model\Household;
use App\Household\Domain\Model\HouseholdMember;
use App\Identity\Domain\Model\UserAccount;
use App\Reminders\Domain\Model\Reminder;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'homeos:seed-demo-data', description: 'Seed rich local-only demo data for Home OS.')]
final class SeedDemoDataCommand extends Command
{
    private const DEMO_PASSWORD = 'password123';

    /** @var array<string, ExpenseCategory> */
    private array $categories = [];

    /** @var array<string, string> */
    private array $members = [];

    /** @var array<string, int> */
    private array $counts = [
        'users' => 0,
        'expenses' => 0,
        'incomeEntries' => 0,
        'bloodTests' => 0,
        'homeTasks' => 0,
        'reminders' => 0,
        'documents' => 0,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ParameterBagInterface $parameters,
        private readonly string $documentsDir,
        private readonly string $healthDocumentsDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset-demo', null, InputOption::VALUE_NONE, 'Delete existing demo households for the selected demo email prefix before seeding.')
            ->addOption('household-name', null, InputOption::VALUE_REQUIRED, 'Demo household name.', 'Demo Household')
            ->addOption('months', null, InputOption::VALUE_REQUIRED, 'Number of months of finance/history data.', '12')
            ->addOption('large', null, InputOption::VALUE_NONE, 'Generate a larger transaction volume.')
            ->addOption('reset-only', null, InputOption::VALUE_NONE, 'Delete demo data for the selected demo email prefix without recreating it.')
            ->addOption('email-prefix', null, InputOption::VALUE_REQUIRED, 'Local demo email prefix. Must start with homeos-demo.', 'homeos-demo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $environment = (string) $this->parameters->get('kernel.environment');
        if ($environment === 'prod') {
            $output->writeln('<error>Refusing to seed demo data in prod.</error>');
            return Command::FAILURE;
        }

        $emailPrefix = strtolower(trim((string) $input->getOption('email-prefix')));
        if (!str_starts_with($emailPrefix, 'homeos-demo')) {
            $output->writeln('<error>Demo email prefix must start with "homeos-demo".</error>');
            return Command::FAILURE;
        }

        $householdName = trim((string) $input->getOption('household-name'));
        if ($householdName === '') {
            $householdName = 'Demo Household';
        }
        if (!str_contains(strtolower($householdName), 'demo')) {
            $householdName .= ' Demo';
        }

        $months = max(1, min(36, (int) $input->getOption('months')));
        $large = (bool) $input->getOption('large');

        $output->writeln('<comment>LOCAL DEMO DATA ONLY. Do not use these credentials for real data.</comment>');

        if ((bool) $input->getOption('reset-demo')) {
            $deleted = $this->resetDemo($emailPrefix);
            $output->writeln(sprintf('<info>Reset removed %d demo household%s for prefix %s.</info>', $deleted, $deleted === 1 ? '' : 's', $emailPrefix));

            if ((bool) $input->getOption('reset-only')) {
                return Command::SUCCESS;
            }
        } elseif ((bool) $input->getOption('reset-only')) {
            $output->writeln('<error>Use --reset-demo together with --reset-only.</error>');
            return Command::FAILURE;
        } elseif ($this->demoExists($emailPrefix)) {
            $output->writeln(sprintf('<comment>Demo data for prefix %s already exists. Use --reset-demo to recreate it.</comment>', $emailPrefix));
            $this->printCredentials($output, $emailPrefix);
            return Command::SUCCESS;
        }

        mt_srand(13013);

        $household = new Household((string) Uuid::new(), $householdName, 'PLN');
        $damian = $household->addMember((string) Uuid::new(), 'Damian Demo', HouseholdMember::TYPE_ADULT, new DateTimeImmutable('1990-05-13'), '#175c4a');
        $partner = $household->addMember((string) Uuid::new(), 'Partner Demo', HouseholdMember::TYPE_ADULT, new DateTimeImmutable('1991-09-20'), '#7c3aed');
        $child = $household->addMember((string) Uuid::new(), 'Child Demo', HouseholdMember::TYPE_CHILD, new DateTimeImmutable('2018-04-02'), '#f97316');
        $this->members = [
            'damian' => $damian->id(),
            'partner' => $partner->id(),
            'child' => $child->id(),
        ];

        $this->entityManager->persist($household);
        $this->seedUsers($household, $emailPrefix);
        $this->seedFinance($household->id(), $months, $large);
        $this->seedHealth($household->id());
        $this->seedHomeMaintenance($household->id());
        $this->seedReminders($household->id());
        $this->seedDocuments($household->id());
        $this->entityManager->flush();

        $output->writeln(sprintf('<info>Seeded %s.</info>', $householdName));
        $this->printCredentials($output, $emailPrefix);
        $output->writeln(sprintf(
            'Generated: %d users, %d expenses, %d income entries, %d blood tests, %d home tasks, %d reminders, %d documents.',
            $this->counts['users'],
            $this->counts['expenses'],
            $this->counts['incomeEntries'],
            $this->counts['bloodTests'],
            $this->counts['homeTasks'],
            $this->counts['reminders'],
            $this->counts['documents'],
        ));
        $output->writeln('<comment>Recreate safely with: php bin/console homeos:seed-demo-data --reset-demo</comment>');
        $output->writeln('<comment>Remove demo data with: php bin/console homeos:seed-demo-data --reset-demo --reset-only</comment>');

        return Command::SUCCESS;
    }

    private function seedUsers(Household $household, string $emailPrefix): void
    {
        $users = [
            ['damian', 'Damian Demo', $this->members['damian']],
            ['partner', 'Partner Demo', $this->members['partner']],
            ['child', 'Child Demo', $this->members['child']],
        ];

        foreach ($users as [$suffix, $name, $memberId]) {
            $email = sprintf('%s-%s@example.test', $emailPrefix, $suffix);
            $user = new UserAccount((string) Uuid::new(), $email, '', $name, $household->id(), $memberId);
            $hash = $this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD);
            $this->entityManager->persist(new UserAccount($user->id(), $email, $hash, $name, $household->id(), $memberId));
            ++$this->counts['users'];
        }
    }

    private function seedFinance(string $householdId, int $months, bool $large): void
    {
        foreach (DefaultExpenseCategories::all() as $category) {
            $entity = new ExpenseCategory((string) Uuid::new(), $householdId, $category['name'], $category['slug'], $category['color']);
            $this->categories[$category['slug']] = $entity;
            $this->entityManager->persist($entity);
        }

        $incomeSource = new IncomeSource((string) Uuid::new(), $householdId, $this->members['damian'], 'Demo salary Damian', 1180000, 'PLN');
        $partnerIncome = new IncomeSource((string) Uuid::new(), $householdId, $this->members['partner'], 'Demo salary Partner', 760000, 'PLN');
        $this->entityManager->persist($incomeSource);
        $this->entityManager->persist($partnerIncome);

        $rules = [
            new FinanceReviewRule((string) Uuid::new(), $householdId, 'expense', 'Biedronka', $this->categories['groceries-home']->id(), null),
            new FinanceReviewRule((string) Uuid::new(), $householdId, 'expense', 'Orlen', $this->categories['transport']->id(), null),
            new FinanceReviewRule((string) Uuid::new(), $householdId, 'expense', 'OBI', $this->categories['other']->id(), null),
            new FinanceReviewRule((string) Uuid::new(), $householdId, 'income', 'Zwrot', null, 'refund'),
        ];
        foreach ($rules as $rule) {
            $this->entityManager->persist($rule);
        }

        $bills = [
            ['Mortgage installment Demo', 'mortgage', 420000, 5, $this->members['damian']],
            ['PGE electricity Demo', 'bills', 36000, 12, $this->members['partner']],
            ['PGNiG gas Demo', 'bills', 26000, 15, $this->members['partner']],
            ['Orange internet Demo', 'phone-internet', 9900, 20, $this->members['damian']],
            ['Play mobile Demo', 'phone-internet', 7600, 25, $this->members['damian']],
            ['Netflix subscription Demo', 'other', 4300, 8, null],
        ];
        $recurringBills = [];
        foreach ($bills as [$name, $slug, $amount, $dueDay, $memberId]) {
            $bill = new RecurringBill((string) Uuid::new(), $householdId, $this->categories[$slug], $name, $amount, 'PLN', $dueDay, $memberId);
            $recurringBills[] = $bill;
            $this->entityManager->persist($bill);
        }

        $today = new DateTimeImmutable('today');
        $expenseRowsPerMonth = $large ? 44 : 18;
        $merchants = [
            ['Biedronka', 'groceries-home', 6500, 26000],
            ['Lidl', 'groceries-home', 7000, 24000],
            ['Żabka', 'groceries-home', 1800, 7200],
            ['Allegro', 'other', 3200, 45000],
            ['Orlen', 'transport', 9000, 28000],
            ['Rossmann', 'health', 2500, 19000],
            ['IKEA', 'other', 15000, 160000],
            ['Media Expert', 'other', 12000, 120000],
            ['Apteka Demo', 'health', 2200, 16000],
            ['Biedronka passport photos Demo', 'other', 4900, 4900],
        ];

        for ($offset = $months - 1; $offset >= 0; --$offset) {
            $monthDate = $today->modify(sprintf('first day of -%d months', $offset));
            $month = $monthDate->format('Y-m');
            $salaryDate = $monthDate->modify('+18 days');

            foreach ([[$incomeSource, 'Salary Damian Demo', 1180000], [$partnerIncome, 'Salary Partner Demo', 760000]] as [$source, $description, $amount]) {
                $entry = new IncomeEntry((string) Uuid::new(), $householdId, $source->id(), $source->memberId(), $description, $amount, 'PLN', $salaryDate);
                $entry->changeClassification('salary', 'reviewed');
                $this->entityManager->persist($entry);
                ++$this->counts['incomeEntries'];
            }

            if ($offset % 4 === 0) {
                $entry = new IncomeEntry((string) Uuid::new(), $householdId, null, $this->members['damian'], 'Zwrot Allegro Demo import needs review', 12450, 'PLN', $monthDate->modify('+9 days'), 'demo-bank-csv', $this->fingerprint('income', $month, 'allegro-refund'));
                $entry->changeClassification('other', 'needs_review', 'Imported bank transaction needs income type check');
                $this->entityManager->persist($entry);
                ++$this->counts['incomeEntries'];
            }

            foreach ($this->categories as $slug => $category) {
                $base = match ($slug) {
                    'groceries-home' => $offset % 5 === 0 ? 230000 : 170000,
                    'transport' => 85000,
                    'health' => 40000,
                    'bills' => 75000,
                    'mortgage' => 420000,
                    'phone-internet' => 25000,
                    default => $offset % 6 === 0 ? 90000 : 60000,
                };
                $this->entityManager->persist(new ExpenseBudget((string) Uuid::new(), $householdId, $category, $month, $base));
            }

            foreach ($recurringBills as $bill) {
                $status = $offset === 0 && $bill->dueDay() < (int) $today->format('d') && str_contains($bill->name(), 'PGE') ? 'planned' : 'paid';
                $paidOn = $status === 'paid' ? $monthDate->modify(sprintf('+%d days', min($bill->dueDay(), 27))) : null;
                $this->entityManager->persist(new RecurringBillPayment((string) Uuid::new(), $householdId, $bill->id(), $month, $status, $paidOn, null));
            }

            for ($i = 0; $i < $expenseRowsPerMonth; ++$i) {
                [$merchant, $slug, $min, $max] = $merchants[$i % count($merchants)];
                $amount = mt_rand($min, $max);
                if ($offset === 0 && $slug === 'groceries-home') {
                    $amount += 18000;
                }

                $date = $monthDate->modify(sprintf('+%d days', 1 + (($i * 3) % 27)));
                $description = sprintf('%s Demo %s', $merchant, $date->format('Y-m-d'));
                if ($merchant === 'Allegro' && $i % 3 === 0) {
                    $description = sprintf('535473------2407 DAMIAN DEMO Allegro marketplace %d,00 PLN %s Transakcja kartą', (int) round($amount / 100), $date->format('Y-m-d'));
                }
                $expense = new Expense((string) Uuid::new(), $householdId, $this->categories[$slug], $description, $amount, 'PLN', $date, $i % 2 === 0 ? $this->members['damian'] : $this->members['partner']);
                $this->entityManager->persist($expense);
                ++$this->counts['expenses'];
            }
        }

        $reviewRows = [
            ['Allegro unknown imported Demo order warranty', 'other', 5570],
            ['Biedronka duplicate-looking Demo import', 'groceries-home', 8420],
            ['Biedronka duplicate-looking Demo import', 'groceries-home', 8420],
            ['Heat pump filter OBI Demo imported', 'other', 14900],
            ['Internet Orange Demo bank import', 'phone-internet', 10500],
        ];

        foreach ($reviewRows as $index => [$description, $slug, $amount]) {
            $expense = new Expense(
                (string) Uuid::new(),
                $householdId,
                $this->categories[$slug],
                $description,
                $amount,
                'PLN',
                $today->modify(sprintf('-%d days', $index + 1)),
                $this->members['damian'],
                'demo-bank-csv',
                $this->fingerprint('expense-review', (string) $index, $description),
            );
            $expense->changeReview('needs_review', 'Imported bank transaction needs category check');
            $this->entityManager->persist($expense);
            ++$this->counts['expenses'];
        }
    }

    private function seedHealth(string $householdId): void
    {
        $tests = [
            ['-13 months', 'Demo Lab Old', [['LDL', 126, 'mg/dl', 0, 115, 'high'], ['Hemoglobina', 14.2, 'g/dl', 12, 16, 'normal']]],
            ['-9 months', 'Demo Lab', [['LDL', 112, 'mg/dl', 0, 115, 'normal'], ['TSH', 2.4, 'µIU/ml', 0.4, 4.0, 'normal']]],
            ['-3 months', 'Demo Lab', [['LDL', 148, 'mg/dl', 0, 115, 'high'], ['Glukoza', 94, 'mg/dl', 70, 99, 'normal']]],
            ['-3 months', 'Demo Lab', [['LDL', 148, 'mg/dl', 0, 115, 'high'], ['Glukoza', 94, 'mg/dl', 70, 99, 'normal']]],
            ['-1 month', 'Demo OCR Import', [['PDW', 890, 'fl', 9, 17, 'high'], ['MCH', 233, 'pg', 27, 33, 'high'], ['DemoMarkerX', 5, '', null, null, 'unknown']]],
        ];

        foreach ($tests as [$dateModifier, $lab, $markers]) {
            $bloodTest = new BloodTest((string) Uuid::new(), $householdId, $this->members['damian'], new DateTimeImmutable($dateModifier), $lab, 'Fake demo health data for UI testing only.');
            foreach ($markers as [$name, $value, $unit, $min, $max, $status]) {
                $bloodTest->addMarker((string) Uuid::new(), $name, (float) $value, (string) $unit, $min === null ? null : (float) $min, $max === null ? null : (float) $max, $status, 'Demo marker; not medical advice.');
            }
            $this->entityManager->persist($bloodTest);
            ++$this->counts['bloodTests'];
        }

        $storedName = $this->writeDemoFile($this->healthDocumentsDir, $householdId, 'demo-health-result.txt', "Fake demo lab result\nLDL 148 mg/dl\n");
        $this->entityManager->persist(new HealthDocument((string) Uuid::new(), $householdId, $this->members['damian'], 'lab_result', 'DEMO fake LDL lab result.txt', $storedName, 'text/plain', 32, new DateTimeImmutable('-1 month')));
    }

    private function seedHomeMaintenance(string $householdId): void
    {
        $today = new DateTimeImmutable('today');
        $tasks = [
            ['Replace heat pump filter Demo', 'Heat pump', '-3 days', 'monthly', 'high', 'Filter is intentionally overdue for Dashboard testing.'],
            ['Water meter reading Demo', 'Utilities', 'today', 'monthly', 'normal', 'Searchable water meter task.'],
            ['Boiler service Demo', 'Boiler', '+7 days', 'yearly', 'high', 'Annual service.'],
            ['Smoke detector check Demo', 'Safety', '+2 days', 'monthly', 'normal', null],
            ['Garden maintenance Demo', 'Garden', '+12 days', 'weekly', 'low', null],
            ['Gutter cleaning Demo', 'Outside', '+25 days', 'yearly', 'normal', null],
            ['Air conditioner cleaning Demo', 'HVAC', '-1 month', 'monthly', 'normal', 'Completed recurring task demo.'],
        ];

        foreach ($tasks as [$title, $area, $due, $recurrence, $priority, $notes]) {
            $task = new HomeMaintenanceTask((string) Uuid::new(), $householdId, $title, $area, $due === 'today' ? $today : new DateTimeImmutable($due), $recurrence, $this->members['damian'], $priority, $notes);
            if (str_contains($title, 'Air conditioner')) {
                $task->complete(new DateTimeImmutable('-20 days'));
            }
            $this->entityManager->persist($task);
            ++$this->counts['homeTasks'];
        }
    }

    private function seedReminders(string $householdId): void
    {
        $today = new DateTimeImmutable('today');
        $reminders = [
            ['Pay demo school fee', 'Overdue reminder for Inbox and digest.', '-2 days', 'none', 'high', null, null, null],
            ['Call insurance agent Demo', 'Insurance renewal question.', 'today', 'none', 'normal', 'document', null, null],
            ['Check passport expiry Demo', 'Passport expires soon in demo documents.', '+4 days', 'monthly', 'high', 'document', null, null],
            ['Review monthly budget Demo', 'Look at over-budget categories.', '+8 days', 'monthly', 'normal', 'expenses', null, null],
            ['Skipped demo reminder', 'Shows skipped reminder state.', '-6 days', 'none', 'low', null, null, 'skip'],
            ['Completed demo reminder', 'Shows completed reminder state.', '-9 days', 'none', 'low', null, null, 'complete'],
        ];

        foreach ($reminders as [$title, $note, $due, $recurrence, $priority, $relatedType, $relatedId, $state]) {
            $reminder = new Reminder((string) Uuid::new(), $householdId, $title, $note, $due === 'today' ? $today : new DateTimeImmutable($due), $recurrence, $relatedType, $relatedId, $priority);
            if ($state === 'skip') {
                $reminder->skip(new DateTimeImmutable('-5 days'));
            } elseif ($state === 'complete') {
                $reminder->complete(new DateTimeImmutable('-8 days'));
            }
            $this->entityManager->persist($reminder);
            ++$this->counts['reminders'];
        }
    }

    private function seedDocuments(string $householdId): void
    {
        $documents = [
            ['Home insurance policy Demo', Document::TYPE_INSURANCE, '-10 months', '+23 days', 'insurance,home,demo', 'Expiring soon to test Dashboard.'],
            ['Passport Demo Child', Document::TYPE_OTHER, '-5 years', '+23 days', 'passport,child,demo', 'Searchable passport demo document.'],
            ['Heat pump warranty Demo', Document::TYPE_WARRANTY, '-22 months', '+1 month', 'warranty,heat pump,demo', 'Warranty expires next month.'],
            ['Boiler service invoice Demo', Document::TYPE_INVOICE, '-3 months', null, 'invoice,boiler,demo', null],
            ['Internet contract Orange Demo', Document::TYPE_CONTRACT, '-13 months', '+11 months', 'internet,contract,orange,demo', null],
            ['IKEA kitchen manual Demo', Document::TYPE_MANUAL, '-18 months', null, 'manual,warranty,demo', null],
            ['PIT tax 2025 Demo', Document::TYPE_TAX, '-2 months', null, 'tax,pit,demo', null],
            ['Expired car insurance Demo', Document::TYPE_INSURANCE, '-14 months', '-14 days', 'insurance,expired,demo', 'Expired on purpose.'],
            ['LDL medical note Demo', Document::TYPE_MEDICAL, '-1 month', null, 'medical,LDL,demo', 'Fake medical document metadata only.'],
        ];

        foreach ($documents as [$title, $type, $issued, $expires, $tags, $note]) {
            $storedName = $this->writeDemoFile($this->documentsDir, $householdId, sprintf('%s.txt', strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? 'demo-document')), sprintf("%s\nFake Home OS demo file.\n", $title));
            $document = new Document(
                (string) Uuid::new(),
                $householdId,
                $title,
                $type,
                $this->members['damian'],
                $issued ? new DateTimeImmutable($issued) : null,
                $expires ? new DateTimeImmutable($expires) : null,
                $tags,
                $note,
                basename($storedName),
                $storedName,
                'text/plain',
                64,
            );
            $this->entityManager->persist($document);
            ++$this->counts['documents'];
        }
    }

    private function resetDemo(string $emailPrefix): int
    {
        $connection = $this->entityManager->getConnection();
        $householdIds = $connection->fetchFirstColumn(
            "SELECT DISTINCT household_id FROM user_accounts WHERE email LIKE :email",
            ['email' => $emailPrefix.'-%@example.test'],
        );

        $safeHouseholdIds = [];
        foreach ($householdIds as $householdId) {
            $name = (string) $connection->fetchOne('SELECT name FROM households WHERE id = :id', ['id' => $householdId]);
            if (str_contains(strtolower($name), 'demo')) {
                $safeHouseholdIds[] = (string) $householdId;
            }
        }

        foreach ($safeHouseholdIds as $householdId) {
            foreach ([$this->documentsDir, $this->healthDocumentsDir] as $baseDir) {
                $this->removeDirectory(sprintf('%s/%s', rtrim($baseDir, '/'), $householdId));
            }

            foreach ([
                'audit_logs',
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
                $connection->delete($table, ['household_id' => $householdId]);
            }
            $connection->delete('user_accounts', ['household_id' => $householdId]);
            $connection->delete('households', ['id' => $householdId]);
        }

        $this->entityManager->clear();

        return count($safeHouseholdIds);
    }

    private function demoExists(string $emailPrefix): bool
    {
        return (bool) $this->entityManager->getConnection()->fetchOne(
            "SELECT 1 FROM user_accounts WHERE email LIKE :email LIMIT 1",
            ['email' => $emailPrefix.'-%@example.test'],
        );
    }

    private function printCredentials(OutputInterface $output, string $emailPrefix): void
    {
        $output->writeln('Demo credentials:');
        $output->writeln(sprintf('  %s-damian@example.test / %s', $emailPrefix, self::DEMO_PASSWORD));
        $output->writeln(sprintf('  %s-partner@example.test / %s', $emailPrefix, self::DEMO_PASSWORD));
        $output->writeln(sprintf('  %s-child@example.test / %s', $emailPrefix, self::DEMO_PASSWORD));
    }

    private function fingerprint(string ...$parts): string
    {
        return hash('sha256', implode('|', array_map(static fn (string $part): string => strtolower(trim($part)), $parts)));
    }

    private function writeDemoFile(string $baseDir, string $householdId, string $name, string $contents): string
    {
        $targetDir = sprintf('%s/%s', rtrim($baseDir, '/'), $householdId);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException(sprintf('Could not create demo document directory "%s".', $targetDir));
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '-', $name) ?: 'demo-document.txt';
        $storedName = sprintf('%s/%s', $householdId, $safeName);
        file_put_contents(sprintf('%s/%s', $targetDir, $safeName), $contents);

        return $storedName;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
