<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Enums\UserLogAction;
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
     * Create user.
     *
     * Creates a user and assigns the role given in `role`. Requires the Administrador role.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data);
        $user->assignRole($role);

        // Audit: record every relevant field on creation (old = null).
        AuditLogger::userLog(UserLogAction::Create, $request->user(), $user, [
            'name'     => ['old' => null, 'new' => $user->name],
            'lastname' => ['old' => null, 'new' => $user->lastname],
            'email'    => ['old' => null, 'new' => $user->email],
            'role'     => ['old' => null, 'new' => $role],
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
     * Delete user.
     *
     * Deletes the given user. Requires the Administrador role.
     */
    public function destroy(User $user): Response
    {
        // Audit before deleting. The changes JSON preserves the id/name even
        // though target_user_id is nulled by the FK once the user is gone.
        AuditLogger::userLog(UserLogAction::Delete, request()->user(), $user, [
            'name'  => ['old' => $user->name, 'new' => null],
            'email' => ['old' => $user->email, 'new' => null],
        ]);

        $user->delete();

        return response()->noContent();
    }
}
