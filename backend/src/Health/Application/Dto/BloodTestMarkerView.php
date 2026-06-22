<?php

namespace App\Health\Application\Dto;

use App\Health\Domain\Model\BloodTestMarker;

final readonly class BloodTestMarkerView
{
    public function __construct(
        public string $id,
        public string $bloodTestId,
        public string $memberId,
        public string $testedAt,
        public string $name,
        public float $value,
        public string $unit,
        public ?float $referenceMin,
        public ?float $referenceMax,
        public string $status,
        public ?string $notes,
    ) {
    }

    public static function fromMarker(BloodTestMarker $marker): self
    {
        return new self(
            $marker->id(),
            $marker->bloodTest()->id(),
            $marker->bloodTest()->memberId(),
            $marker->bloodTest()->testedAt()->format('Y-m-d'),
            $marker->name(),
            $marker->value(),
            $marker->unit(),
            $marker->referenceMin(),
            $marker->referenceMax(),
            $marker->status(),
            $marker->notes(),
        );
    }
}
