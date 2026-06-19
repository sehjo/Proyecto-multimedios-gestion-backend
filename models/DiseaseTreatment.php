<?php

class DiseaseTreatment
{
    private ?int $id;
    private ?int $diseaseId;
    private ?int $drugId;
    private ?string $descriptions;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        ?int $diseaseId = null,
        ?int $drugId = null,
        ?string $descriptions = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->diseaseId = $diseaseId;
        $this->drugId = $drugId;
        $this->descriptions = $descriptions;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            isset($row['disease_id']) ? (int) $row['disease_id'] : null,
            isset($row['drugs']) ? (int) $row['drugs'] : null,
            $row['descriptions'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getDiseaseId(): ?int
    {
        return $this->diseaseId;
    }

    public function setDiseaseId(?int $diseaseId): void
    {
        $this->diseaseId = $diseaseId;
    }

    public function getDrugId(): ?int
    {
        return $this->drugId;
    }

    public function setDrugId(?int $drugId): void
    {
        $this->drugId = $drugId;
    }

    public function getDescriptions(): ?string
    {
        return $this->descriptions;
    }

    public function setDescriptions(?string $descriptions): void
    {
        $this->descriptions = $descriptions;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
