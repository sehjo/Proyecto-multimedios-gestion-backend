<?php

class UserResource
{
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'user_type_id' => $user->getUserTypeId(),
            // Primary role (derived from user_type_id), all roles, and status.
            'role' => $user->getRoleName(),
            'roles' => $user->getRoleNames() ?? array_values(array_filter([$user->getRoleName()])),
            'status' => $user->getStatus(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];
    }

    public static function collection(array $users): array
    {
        return array_map(fn (User $user) => self::toArray($user), $users);
    }
}
