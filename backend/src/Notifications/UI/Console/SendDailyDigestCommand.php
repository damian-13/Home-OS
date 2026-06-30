<?php

namespace App\Notifications\UI\Console;

use App\Household\Domain\Model\Household;
use App\Notifications\Application\Service\DailyNotificationDigestBuilder;
use App\Notifications\Application\Service\DailyNotificationDigestRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('household', null, InputOption::VALUE_REQUIRED, 'Generate a digest for one household id.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Render the digest without attempting email delivery. This is the current MVP behavior.');
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

        foreach ($households as $household) {
            $digest = $this->builder->build($household->id());

            $io->section(sprintf('Daily digest for %s', $household->name()));
            $io->writeln($this->renderer->renderText($digest));
            $io->note('Email delivery is not configured in this MVP. Digest was rendered locally only.');
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
}
