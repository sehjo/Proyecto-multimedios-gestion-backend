<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Http\Requests\User\IndexUserRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Enums\UserLogAction;
use App\Enums\UserStatus;
use App\Support\AdminGuard;
use App\Support\AuditLogger;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List users.
     *
     * Returns the paginated list of users with their roles. Supports optional
     * filters: `name` (matches name/lastname/email), `role`, `status`, and
     * `per_page` (1-100, default 15). Requires the `users.read` permission.
     */
    public function index(IndexUserRequest $request)
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        $users = User::query()
            ->when(! empty($filters['name']), function ($q) use ($filters) {
                $term = $filters['name'];
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                        ->orWhere('lastname', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['role']), fn ($q) => $q->whereHas(
                'roles',
                fn ($r) => $r->where('name', $filters['role'])
            ))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * User stats by role.
     *
     * Returns the user count per role (for dashboards). Requires the
     * `users.read` permission.
     */
    public function statsByRole(): JsonResponse
    {
        // Count users per role. Every defined role is included (0 if unused).
        $counts = Role::orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->users()->count()]);

        return response()->json([
            'success' => true,
            'data'    => $counts,
        ]);
    }

    /**
     * User stats by status.
     *
     * Returns the user count per status (ACTIVE / INACTIVE). Requires the
     * `users.read` permission.
     */
    public function statsByStatus(): JsonResponse
    {
        $counts = [];
        foreach (UserStatus::cases() as $status) {
            $counts[$status->value] = User::where('status', $status->value)->count();
        }

        return response()->json([
            'success' => true,
            'data'    => $counts,
        ]);
    }

    /**
     * Create user.
     *
     * Creates a user and assigns the role given in `role`. Requires the Administrador role.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        // New users start active.
        $data['status'] = UserStatus::Active->value;

        $user = User::create($data);
        $user->assignRole($role);

        // Audit: record every relevant field on creation (old = null).
        AuditLogger::userLog(UserLogAction::Create, $request->user(), $user, [
            'name'     => ['old' => null, 'new' => $user->name],
            'lastname' => ['old' => null, 'new' => $user->lastname],
            'email'    => ['old' => null, 'new' => $user->email],
            'role'     => ['old' => null, 'new' => $role],
            'status'   => ['old' => null, 'new' => $user->status->value],
        ]);

        return response()->json(new UserResource($user->load('roles')));
    }

    /**
     * Show user.
     *
     * Returns a user by id. Requires the Administrador role.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    /**
     * Update user.
     *
     * Updates the user's name, lastname and email. The role is NOT changed here;
     * it is managed exclusively via PUT /users/{user}/role. Requires the
     * `users.update` permission.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        // Self-protection: user management is for OTHER users. You cannot edit
        // your own account through this endpoint.
        if ($request->user()->id === $user->id) {
            return $this->selfActionError('No puedes editar tu propio usuario.');
        }

        $data = $request->validated();

        // Snapshot before the change to build the audit diff.
        $before = ['name' => $user->name, 'lastname' => $user->lastname, 'email' => $user->email];

        $user->update($data);

        // Audit: only the fields that actually changed; nothing logged if no change.
        $fields = AuditLogger::diff($before, [
            'name'     => $user->name,
            'lastname' => $user->lastname,
            'email'    => $user->email,
        ]);
        if (! empty($fields)) {
            AuditLogger::userLog(UserLogAction::Update, $request->user(), $user, $fields);
        }

        return response()->json(new UserResource($user->load('roles')));
    }

    /**
     * Change user status.
     *
     * Activates or deactivates a user (ACTIVE ↔ INACTIVE). Deactivating a user
     * revokes their active sessions. Fine-grained permission is enforced per
     * direction: reactivating (→ ACTIVE) requires `users.update`; deactivating
     * (→ INACTIVE) requires `users.delete`.
     */
    public function changeStatus(ChangeUserStatusRequest $request, User $user): JsonResponse
    {
        $newStatus = UserStatus::from($request->validated()['status']);
        $oldStatus = $user->status;

        // Self-protection: an admin cannot change their own status.
        if ($request->user()->id === $user->id) {
            return $this->selfActionError('No puedes cambiar tu propio estado.');
        }

        // Direction-based permission check:
        //   → INACTIVE  requires users.delete  (treated as a destructive action)
        //   → ACTIVE    requires users.update   (treated as an update action)
        if ($newStatus === UserStatus::Inactive && ! $request->user()->can('users.delete')) {
            return $this->forbiddenError('No tienes permiso para desactivar usuarios.');
        }
        if ($newStatus === UserStatus::Active && ! $request->user()->can('users.update')) {
            return $this->forbiddenError('No tienes permiso para activar usuarios.');
        }

        // No-op: same status requested, nothing to do.
        if ($newStatus === $oldStatus) {
            return response()->json(new UserResource($user->load('roles')));
        }

        // Anti-lockout: cannot deactivate the last active administrator.
        if ($newStatus === UserStatus::Inactive && AdminGuard::isLastActiveAdmin($user)) {
            return $this->lastAdminError('No puedes desactivar al último administrador activo.');
        }

        $user->update(['status' => $newStatus]);

        // Deactivating closes the user's sessions.
        if ($newStatus === UserStatus::Inactive) {
            $user->tokens()->delete();
        }

        AuditLogger::userLog(
            $newStatus === UserStatus::Inactive ? UserLogAction::Inactive : UserLogAction::Reactive,
            $request->user(),
            $user,
            ['status' => ['old' => $oldStatus->value, 'new' => $newStatus->value]],
        );

        return response()->json(new UserResource($user->load('roles')));
    }

    private function selfActionError(string $message): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => 'SELF_ACTION_FORBIDDEN',
        ], 403);
    }

    private function forbiddenError(string $message): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => 'FORBIDDEN',
        ], 403);
    }

    private function lastAdminError(string $message): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => 'LAST_ADMIN',
        ], 409);
    }
}
