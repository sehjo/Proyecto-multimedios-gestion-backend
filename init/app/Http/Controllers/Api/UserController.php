<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\User\ChangeUserStatusRequest;
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
     * Returns the paginated list of users with their roles. Requires the Administrador role.
     */
    public function index(Request $request)
    {
        $users = User::paginate();

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
     * Updates the user's data and, if `role` is sent, replaces their role.
     * Requires the Administrador role.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Snapshot before the change to build the audit diff.
        $oldRole = $user->getRoleNames()->first();
        $before  = ['name' => $user->name, 'lastname' => $user->lastname, 'email' => $user->email];

        // A role change has self-protection and anti-lockout guards.
        if ($role !== null && $role !== $oldRole) {
            if ($request->user()->id === $user->id) {
                return $this->selfActionError('No puedes cambiar tu propio rol.');
            }
            if ($oldRole === AdminGuard::ADMIN_ROLE && AdminGuard::isLastActiveAdmin($user)) {
                return $this->lastAdminError('No puedes quitar el rol al último administrador activo.');
            }
        }

        $user->update($data);

        if ($role !== null) {
            $user->syncRoles([$role]);
        }

        // Audit: only the fields that actually changed; nothing logged if no change.
        $fields = AuditLogger::diff($before, [
            'name'     => $user->name,
            'lastname' => $user->lastname,
            'email'    => $user->email,
        ]);
        if ($role !== null && $role !== $oldRole) {
            $fields['role'] = ['old' => $oldRole, 'new' => $role];
        }
        if (! empty($fields)) {
            AuditLogger::userLog(UserLogAction::Update, $request->user(), $user, $fields);
        }

        return response()->json(new UserResource($user->load('roles')));
    }

    /**
     * Change user status.
     *
     * Activates or deactivates a user (ACTIVE ↔ INACTIVE). Deactivating a user
     * revokes their active sessions. Requires the `users.update` permission.
     */
    public function changeStatus(ChangeUserStatusRequest $request, User $user): JsonResponse
    {
        $newStatus = UserStatus::from($request->validated()['status']);
        $oldStatus = $user->status;

        // Self-protection: an admin cannot change their own status.
        if ($request->user()->id === $user->id) {
            return $this->selfActionError('No puedes cambiar tu propio estado.');
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

    private function lastAdminError(string $message): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => $message,
            'error_code' => 'LAST_ADMIN',
        ], 409);
    }
}
