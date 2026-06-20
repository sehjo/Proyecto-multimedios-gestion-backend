<?php

class User
{
    private ?int $id;
    private ?string $name;
    private ?string $lastname;
    private ?string $email;
    private ?string $password;
    private ?int $userTypeId;
    private ?string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    /** Role name resolved via JOIN with users_types (not a users column). */
    private ?string $roleName = null;

    /** All role names the user holds (loaded on demand from user_has_roles). */
    private ?array $roleNames = null;

    public function __construct(
        ?int $id = null,
        ?string $name = null,
        ?string $lastname = null,
        ?string $email = null,
        ?string $password = null,
        ?int $userTypeId = null,
        ?string $status = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->password = $password;
        $this->userTypeId = $userTypeId;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        $user = new self(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['lastname'] ?? null,
            $row['email'] ?? null,
            $row['password'] ?? null,
            isset($row['user_type_id']) ? (int) $row['user_type_id'] : null,
            $row['status'] ?? 'ACTIVE',
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        // Optional role name when the query JOINs users_types.
        if (isset($row['role_name'])) {
            $user->setRoleName($row['role_name']);
        }

        return $user;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(?string $roleName): void
    {
        $this->roleName = $roleName;
    }

    /** @return array<int, string>|null */
    public function getRoleNames(): ?array
    {
        return $this->roleNames;
    }

    /** @param array<int, string> $roleNames */
    public function setRoleNames(array $roleNames): void
    {
        $this->roleNames = $roleNames;
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
