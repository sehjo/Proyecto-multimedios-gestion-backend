<?php

class Diagnosis
{
    private ?int $id;
    private ?string $name;
    private ?int $diseaseId;
    private ?int $patientId;
    private ?int $diagnosesBy;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?int $diseaseId = null,
        ?int $patientId = null,
        ?int $diagnosesBy = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->diseaseId = $diseaseId;
        $this->patientId = $patientId;
        $this->diagnosesBy = $diagnosesBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            isset($row['disease_id']) ? (int) $row['disease_id'] : null,
            isset($row['patient_id']) ? (int) $row['patient_id'] : null,
            isset($row['diagnoses_by']) ? (int) $row['diagnoses_by'] : null,
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDiseaseId(): ?int
    {
        return $this->diseaseId;
    }

    public function setDiseaseId(?int $diseaseId): void
    {
        $this->diseaseId = $diseaseId;
    }

    public function getPatientId(): ?int
    {
        return $this->patientId;
    }

    public function setPatientId(?int $patientId): void
    {
        $this->patientId = $patientId;
    }

    public function getDiagnosesBy(): ?int
    {
        return $this->diagnosesBy;
    }

    public function setDiagnosesBy(?int $diagnosesBy): void
    {
        $this->diagnosesBy = $diagnosesBy;
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
