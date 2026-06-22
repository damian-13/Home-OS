<?php

namespace App\Health\Application\Dto;

use App\Health\Domain\Model\BloodTest;

final readonly class BloodTestView
{
    /**
     * @param list<BloodTestMarkerView> $markers
     */
    public function __construct(
        public string $id,
        public string $memberId,
        public string $testedAt,
        public ?string $labName,
        public ?string $notes,
        public ?string $sourceDocumentId,
        public array $markers,
    ) {
    }

    public static function fromBloodTest(BloodTest $bloodTest): self
    {
        return new self(
            $bloodTest->id(),
            $bloodTest->memberId(),
            $bloodTest->testedAt()->format('Y-m-d'),
            $bloodTest->labName(),
            $bloodTest->notes(),
            $bloodTest->sourceDocumentId(),
            array_map(static fn ($marker) => BloodTestMarkerView::fromMarker($marker), $bloodTest->markers()),
        );
    }
}
