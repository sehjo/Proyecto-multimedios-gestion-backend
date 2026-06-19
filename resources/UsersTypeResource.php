<?php

class UsersTypeResource
{
    public static function toArray(UserType $userType): array
    {
        return [
            'id' => $userType->getId(),
            'name' => $userType->getName(),
            'created_at' => $userType->getCreatedAt(),
            'updated_at' => $userType->getUpdatedAt(),
        ];
    }

    public static function collection(array $userTypes): array
    {
        return array_map(fn (UserType $userType) => self::toArray($userType), $userTypes);
    }
}
