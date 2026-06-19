<?php

class PatientResource
{
    public static function toArray(Patient $patient): array
    {
        return [
            'id' => $patient->getId(),
            'name' => $patient->getName(),
            'lastname' => $patient->getLastname(),
            'nick' => $patient->getNick(),
            'suffering' => $patient->getSuffering(),
            'register_by' => $patient->getRegisterBy(),
            'user' => $patient->getUser() ? UserResource::toArray($patient->getUser()) : null,
            'created_at' => $patient->getCreatedAt(),
            'updated_at' => $patient->getUpdatedAt(),
        ];
    }

    public static function collection(array $patients): array
    {
        return array_map(fn (Patient $patient) => self::toArray($patient), $patients);
    }
}
