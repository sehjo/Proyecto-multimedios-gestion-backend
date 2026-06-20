<?php

/**
 * Central writer for the audit logs (log_users / log_roles), ported from the
 * Laravel App\Support\AuditLogger. Builds the `changes` payload with the shared
 * schema { performed_by:{id,name}, target:{id,name}, fields:{ field:{old,new} } }.
 */
class AuditLogger
{
    /**
     * Record a users-log entry.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     */
    public static function userLog(string $action, ?User $actor, ?User $target, array $fields = []): void
    {
        LogUserRepository::create(
            $action,
            $actor?->getId(),
            $target?->getId(),
            self::buildChanges($actor, $target?->getId(), self::userName($target), $fields)
        );
    }

    /**
     * Record a roles-log entry. The target may be a UserType (role) or its name.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     */
    public static function roleLog(string $action, ?User $actor, UserType|string|null $target, array $fields = []): void
    {
        $targetId = $target instanceof UserType ? $target->getId() : null;
        $targetName = $target instanceof UserType ? $target->getName() : $target;

        LogRoleRepository::create(
            $action,
            $actor?->getId(),
            $targetId,
            self::buildChanges($actor, $targetId, $targetName, $fields)
        );
    }

    /**
     * Build a `fields` diff: one entry per field that actually changed.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $old, array $new): array
    {
        $fields = [];
        foreach ($new as $key => $newValue) {
            $oldValue = $old[$key] ?? null;
            if ($oldValue !== $newValue) {
                $fields[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $fields;
    }

    /** A masked field entry for a password change. The value is never stored. */
    public static function maskedPassword(): array
    {
        return ['old' => '***', 'new' => '***'];
    }

    private static function userName(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        return trim(($user->getName() ?? '') . ' ' . ($user->getLastname() ?? '')) ?: null;
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     * @return array<string, mixed>
     */
    private static function buildChanges(?User $actor, mixed $targetId, ?string $targetName, array $fields): array
    {
        return [
            'performed_by' => $actor ? ['id' => $actor->getId(), 'name' => self::userName($actor)] : null,
            'target' => ['id' => $targetId, 'name' => $targetName],
            'fields' => $fields,
        ];
    }
}
