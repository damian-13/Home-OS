<?php

namespace App\Health\Application\Command;

use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AddBloodTestHandler implements CommandHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return AddBloodTestCommand::class;
    }

    public function __invoke(AddBloodTestCommand $command): string
    {
        if ($command->markers === []) {
            throw new InvalidArgumentException('Blood test must contain at least one marker.');
        }

        $bloodTest = new BloodTest(
            (string) Uuid::new(),
            $command->householdId,
            $command->memberId,
            new DateTimeImmutable($command->testedAt),
            $command->labName ? trim($command->labName) : null,
            $command->notes ? trim($command->notes) : null,
        );

        foreach ($command->markers as $marker) {
            $name = trim((string) ($marker['name'] ?? ''));
            $unit = trim((string) ($marker['unit'] ?? ''));

            if ($name === '' || $unit === '') {
                throw new InvalidArgumentException('Marker name and unit are required.');
            }

            $bloodTest->addMarker(
                (string) Uuid::new(),
                $name,
                (float) ($marker['value'] ?? 0),
                $unit,
                $this->nullableFloat($marker['referenceMin'] ?? null),
                $this->nullableFloat($marker['referenceMax'] ?? null),
                $this->normalizeStatus($marker['status'] ?? null),
                isset($marker['notes']) && trim((string) $marker['notes']) !== '' ? trim((string) $marker['notes']) : null,
            );
        }

        $this->health->saveBloodTest($bloodTest);

        return $bloodTest->id();
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = (string) $status;

        if (in_array($status, ['normal', 'low', 'high'], true)) {
            return $status;
        }

        return 'unknown';
    }
}
