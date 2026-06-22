<?php

namespace App\Health\Domain\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'blood_test_markers')]
#[ORM\Index(name: 'IDX_BLOOD_TEST_MARKERS_NAME', columns: ['marker_name'])]
class BloodTestMarker
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: BloodTest::class, inversedBy: 'markers')]
    #[ORM\JoinColumn(name: 'blood_test_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BloodTest $bloodTest;

    #[ORM\Column(name: 'marker_name', type: 'string', length: 80)]
    private string $name;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'string', length: 32)]
    private string $unit;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $referenceMin;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $referenceMax;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    public function __construct(
        string $id,
        BloodTest $bloodTest,
        string $name,
        float $value,
        string $unit,
        ?float $referenceMin,
        ?float $referenceMax,
        string $status,
        ?string $notes,
    ) {
        $this->id = $id;
        $this->bloodTest = $bloodTest;
        $this->name = $name;
        $this->value = $value;
        $this->unit = $unit;
        $this->referenceMin = $referenceMin;
        $this->referenceMax = $referenceMax;
        $this->status = $status;
        $this->notes = $notes;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function bloodTest(): BloodTest
    {
        return $this->bloodTest;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function referenceMin(): ?float
    {
        return $this->referenceMin;
    }

    public function referenceMax(): ?float
    {
        return $this->referenceMax;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
