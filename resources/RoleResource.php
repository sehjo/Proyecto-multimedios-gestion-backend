<?php

class RoleResource
{
    /**
     * @param  array<int, string>|null  $permissions  permission names, when loaded
     */
    public static function toArray(UserType $role, ?array $permissions = null): array
    {
        $data = [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'created_at' => $role->getCreatedAt(),
            'updated_at' => $role->getUpdatedAt(),
        ];

        if ($permissions !== null) {
            $data['permissions'] = $permissions;
        }

        return $data;
    }

    /**
     * @param  array<int, UserType>  $roles
     */
    public static function collection(array $roles): array
    {
        return array_map(fn (UserType $role) => self::toArray($role), $roles);
    }
}
