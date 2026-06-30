<?php

namespace App\Notifications\UI\Console;

use App\Household\Domain\Model\Household;
use App\Identity\Domain\Model\UserAccount;
use App\Notifications\Application\Service\DailyNotificationDigestBuilder;
use App\Notifications\Application\Service\DailyNotificationDigestRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'homeos:send-daily-digest',
    description: 'Generate the Home OS daily notification digest. Email sending is not configured yet, so the digest is rendered locally.',
)]
final class SendDailyDigestCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DailyNotificationDigestBuilder $builder,
        private readonly DailyNotificationDigestRenderer $renderer,
        private readonly MailerInterface $mailer,
        private readonly string $notificationSenderEmail,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('household', null, InputOption::VALUE_REQUIRED, 'Generate a digest for one household id.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Render the digest without attempting email delivery.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $householdId = $input->getOption('household');
        $households = $this->households(is_string($householdId) && $householdId !== '' ? $householdId : null);

        if ($households === []) {
            $io->warning('No households found for daily digest.');

            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable();
        foreach ($households as $household) {
            $digest = $this->builder->build($household->id(), $now);
            $recipients = $this->recipients($household->id(), $now);

            $io->section(sprintf('Daily digest for %s', $household->name()));
            $io->writeln($this->renderer->renderText($digest));

            if ($recipients === []) {
                $io->note('No enabled digest recipients for this household.');
                continue;
            }

            $io->text(sprintf('Recipients: %s', implode(', ', array_map(static fn (UserAccount $user): string => $user->email(), $recipients))));

            if ((bool) $input->getOption('dry-run')) {
                $io->note('Dry run: no email was sent.');
                continue;
            }

            foreach ($recipients as $recipient) {
                $this->mailer->send((new Email())
                    ->from($this->notificationSenderEmail)
                    ->to($recipient->email())
                    ->subject(sprintf('Home OS daily digest: %d item%s need attention', $digest->totalItems, $digest->totalItems === 1 ? '' : 's'))
                    ->text($this->renderer->renderText($digest)));
            }

            $io->success(sprintf('Sent daily digest to %d recipient%s.', count($recipients), count($recipients) === 1 ? '' : 's'));
        }

        $io->success(sprintf('Generated %d daily digest%s.', count($households), count($households) === 1 ? '' : 's'));

        return Command::SUCCESS;
    }

    /**
     * @return list<Household>
     */
    private function households(?string $householdId): array
    {
        $repository = $this->entityManager->getRepository(Household::class);

        if ($householdId !== null) {
            $household = $repository->find($householdId);

            return $household instanceof Household ? [$household] : [];
        }

        return array_values(array_filter(
            $repository->findAll(),
            static fn (mixed $household): bool => $household instanceof Household,
        ));
    }

    /**
     * @return list<UserAccount>
     */
    private function recipients(string $householdId, \DateTimeImmutable $now): array
    {
        $users = $this->entityManager->getRepository(UserAccount::class)->findBy(['householdId' => $householdId]);
        $hour = (int) $now->format('G');

        return array_values(array_filter($users, static function (mixed $user) use ($hour): bool {
            if (!$user instanceof UserAccount) {
                return false;
            }

            if (!$user->notificationDigestEnabled()) {
                return false;
            }

            if (!filter_var($user->email(), FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            return $user->notificationDigestHour() === null || $user->notificationDigestHour() === $hour;
        }));
    }
}
