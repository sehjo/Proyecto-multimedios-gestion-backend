<?php

class Disease
{
    private ?int $id;
    private ?string $name;
    private ?string $technicalName;
    private ?string $description;
    private ?int $priorityId;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?string $technicalName = null,
        ?string $description = null,
        ?int $priorityId = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->technicalName = $technicalName;
        $this->description = $description;
        $this->priorityId = $priorityId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['technincal_name'] ?? null,
            $row['description'] ?? null,
            isset($row['priority_id']) ? (int) $row['priority_id'] : null,
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

    public function getTechnicalName(): ?string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(?string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPriorityId(): ?int
    {
        return $this->priorityId;
    }

    public function setPriorityId(?int $priorityId): void
    {
        $this->priorityId = $priorityId;
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
