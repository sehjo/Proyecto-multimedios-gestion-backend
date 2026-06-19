<?php

class User
{
    private ?int $id;
    private ?string $name;
    private ?string $lastname;
    private ?string $email;
    private ?string $password;
    private ?int $userTypeId;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?string $lastname = null,
        ?string $email = null,
        ?string $password = null,
        ?int $userTypeId = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->password = $password;
        $this->userTypeId = $userTypeId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['lastname'] ?? null,
            $row['email'] ?? null,
            $row['password'] ?? null,
            isset($row['user_type_id']) ? (int) $row['user_type_id'] : null,
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getUserTypeId(): ?int
    {
        return $this->userTypeId;
    }

    public function setUserTypeId(?int $userTypeId): void
    {
        $this->userTypeId = $userTypeId;
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
