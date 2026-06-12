<?php

namespace App\Support;

use App\Enums\RoleLogAction;
use App\Enums\UserLogAction;
use App\Models\LogRole;
use App\Models\LogUser;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Central writer for the audit logs (log_users / log_roles).
 *
 * Builds the `changes` JSON with the shared schema
 * { performed_by:{id,name}, target:{id,name}, fields:{ field:{old,new} } }
 * so the construction is not repeated across controllers.
 */
class AuditLogger
{
    /**
     * Record a users-log entry.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     */
    public static function userLog(UserLogAction $action, ?User $actor, ?User $target, array $fields = []): LogUser
    {
        return LogUser::create([
            'performed_by_id' => $actor?->id,
            'target_user_id'  => $target?->id,
            'action'          => $action->value,
            'changes'         => self::buildChanges($actor, $target?->id, $target?->name, $fields),
            'timestamp'       => now(),
        ]);
    }

    /**
     * Record a roles-log entry. The target may be a Role model or its name.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     */
    public static function roleLog(RoleLogAction $action, ?User $actor, Role|string|null $target, array $fields = []): LogRole
    {
        $targetId   = $target instanceof Role ? $target->id : null;
        $targetName = $target instanceof Role ? $target->name : $target;

        return LogRole::create([
            'performed_by_id' => $actor?->id,
            'target_role_id'  => $targetId,
            'action'          => $action->value,
            'changes'         => self::buildChanges($actor, $targetId, $targetName, $fields),
            'timestamp'       => now(),
        ]);
    }

    /**
     * Build a `fields` diff: one entry per field that actually changed.
     * Pass scalar old/new values keyed by field name.
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

    /**
     * A field entry for a password change. The value is never stored.
     *
     * @return array{old: string, new: string}
     */
    public static function maskedPassword(): array
    {
        return ['old' => '***', 'new' => '***'];
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     * @return array<string, mixed>
     */
    private static function buildChanges(?User $actor, mixed $targetId, ?string $targetName, array $fields): array
    {
        return [
            'performed_by' => $actor ? ['id' => $actor->id, 'name' => $actor->name] : null,
            'target'       => ['id' => $targetId, 'name' => $targetName],
            'fields'       => $fields,
        ];
    }
}
