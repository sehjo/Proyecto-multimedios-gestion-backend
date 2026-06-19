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
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];
    }

    public static function collection(array $users): array
    {
        return array_map(fn (User $user) => self::toArray($user), $users);
    }
}
