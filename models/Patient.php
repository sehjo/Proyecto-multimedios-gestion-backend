<?php

class Patient
{
    private ?int $id;
    private ?string $name;
    private ?string $lastname;
    private ?string $nick;
    private ?string $suffering;
    private ?int $registerBy;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?User $user = null;

    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?string $lastname = null,
        ?string $nick = null,
        ?string $suffering = null,
        ?int $registerBy = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastname = $lastname;
        $this->nick = $nick;
        $this->suffering = $suffering;
        $this->registerBy = $registerBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['lastname'] ?? null,
            $row['nick'] ?? null,
            $row['suffering'] ?? null,
            isset($row['register_by']) ? (int) $row['register_by'] : null,
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getNick(): ?string
    {
        return $this->nick;
    }

    public function setNick(?string $nick): void
    {
        $this->nick = $nick;
    }

    public function getSuffering(): ?string
    {
        return $this->suffering;
    }

    public function setSuffering(?string $suffering): void
    {
        $this->suffering = $suffering;
    }

    public function getRegisterBy(): ?int
    {
        return $this->registerBy;
    }

    public function setRegisterBy(?int $registerBy): void
    {
        $this->registerBy = $registerBy;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
