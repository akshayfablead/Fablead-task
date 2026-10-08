<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Default roles that cannot be deleted.
     */
    private const DEFAULT_ROLES = [
        'Admin',
        'Manager',
        'User',
    ];

    /**
     * Roles that cannot be renamed.
     */
    private const PROTECTED_ROLE_NAMES = [
        'Admin',
        'Manager',
        'User',
    ];

    /**
     * Display the admin management page.
     */
    public function index(): View
    {
        return view('admin.index', [
            'accounts' => User::query()
                ->with('roles')
                ->orderBy('id')
                ->paginate(15),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'permissions' => config('access.permissions', []),
        ]);
    }

    /**
     * Create a new account.
     */
    public function storeAccount(Request $request): JsonResponse
    {
        [$data, $role, $overrides] = $this->accountData($request);

        DB::transaction(function () use ($data, $role, $overrides): void {
            $user = User::create($data);
            $user->assignRole($role);
            $user->permission_overrides = $overrides;
            $user->save();
        });

        return response()->json([
            'message' => 'Account created successfully.',
        ], 201);
    }

    /**
     * Update an existing account.
     */
    public function updateAccount(
        Request $request,
        User $user
    ): JsonResponse {
        $this->ensureAccountCanBeModified($user);

        [$data, $role, $overrides] = $this->accountData(
            $request,
            $user
        );

        DB::transaction(function () use (
            $user,
            $data,
            $role,
            $overrides
        ): void {
            $user->update($data);

            $user->syncRoles([$role]);

            $user->permission_overrides = $overrides;
            $user->save();

            $this->invalidateUserSessions(
                $user,
                $data
            );
        });

        return response()->json([
            'message' => 'Account updated successfully.',
        ]);
    }

    /**
     * Delete an existing account.
     */
    public function deleteAccount(User $user): JsonResponse
    {
        $this->ensureAccountCanBeModified($user);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedUser->records()->exists(),
                422,
                "Delete this account's records first."
            );

            $this->invalidateSessions($lockedUser);

            $lockedUser->delete();
        });

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Create a new role.
     */
    public function storeRole(Request $request): JsonResponse
    {
        $data = $this->roleData($request);

        DB::transaction(function () use ($data): void {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions(
                $data['permissions'] ?? []
            );
        });

        return response()->json([
            'message' => 'Role created successfully.',
        ], 201);
    }

    /**
     * Update an existing role.
     */
    public function updateRole(
        Request $request,
        Role $role
    ): JsonResponse {
        $this->ensureRoleCanBeModified($role);

        $data = $this->roleData($request, $role);

        $this->ensureDefaultRoleNameIsProtected(
            $role,
            $data['name']
        );

        DB::transaction(function () use ($role, $data): void {
            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions(
                $data['permissions'] ?? []
            );
        });

        return response()->json([
            'message' => 'Role updated successfully.',
        ]);
    }

    /**
     * Delete an existing role.
     */
    public function deleteRole(Role $role): JsonResponse
    {
        $this->ensureDefaultRoleIsProtected($role);

        DB::transaction(function () use ($role): void {
            $lockedRole = Role::query()
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedRole->users()->exists(),
                422,
                'Reassign accounts before deleting this role.'
            );

            $lockedRole->delete();
        });

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Validate and prepare account data.
     */
    private function accountData(
        Request $request,
        ?User $user = null
    ): array {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')
                    ->ignore($user?->id),
            ],

            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                Password::min(6),
                'confirmed',
            ],

            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNot('name', 'Admin'),
            ],

            'overrides' => [
                'nullable',
                'array:view,create,update,delete',
            ],

            'overrides.*' => [
                Rule::in([
                    'inherit',
                    'allow',
                    'deny',
                ]),
            ],
        ]);

        $role = Role::query()
            ->whereKey($data['role_id'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $overrides = $this->preparePermissionOverrides(
            $data['overrides'] ?? []
        );

        $fields = collect($data)
            ->only([
                'name',
                'email',
                'password',
            ])
            ->all();

        if (empty($fields['password'])) {
            unset($fields['password']);
        }

        return [
            $fields,
            $role,
            $overrides,
        ];
    }

    /**
     * Convert account permission override input
     * into the format stored on the user.
     */
    private function preparePermissionOverrides(
        array $overrides
    ): array {
        $prepared = [];

        foreach ($overrides as $action => $mode) {
            if ($mode === 'inherit') {
                continue;
            }

            $prepared['records.' . $action] = $mode === 'allow';
        }

        return $prepared;
    }

    /**
     * Validate role data.
     */
    private function roleData(
        Request $request,
        ?Role $role = null
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                Rule::notIn([
                    'Admin',
                    'admin',
                    'ADMIN',
                ]),
                Rule::unique('roles', 'name')
                    ->ignore($role?->id),
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
                'distinct',
                Rule::in(
                    config('access.permissions', [])
                ),
            ],
        ]);
    }

    /**
     * Prevent modification of protected accounts.
     */
    private function ensureAccountCanBeModified(
        User $user
    ): void {
        abort_if(
            $user->isAdmin(),
            403,
            'Admin accounts are protected.'
        );
    }

    /**
     * Prevent modification of protected roles.
     */
    private function ensureRoleCanBeModified(
        Role $role
    ): void {
        abort_if(
            $role->guard_name !== 'web'
            || $role->name === 'Admin',
            403,
            'This role cannot be modified.'
        );
    }

    /**
     * Prevent default roles from being renamed.
     */
    private function ensureDefaultRoleNameIsProtected(
        Role $role,
        string $newName
    ): void {
        if (
            in_array(
                $role->name,
                self::PROTECTED_ROLE_NAMES,
                true
            )
            && $newName !== $role->name
        ) {
            throw ValidationException::withMessages([
                'name' => 'Default role names cannot be changed.',
            ]);
        }
    }

    /**
     * Prevent deletion of default roles.
     */
    private function ensureDefaultRoleIsProtected(
        Role $role
    ): void {
        abort_if(
            in_array(
                $role->name,
                self::DEFAULT_ROLES,
                true
            ),
            403,
            'Default roles are protected.'
        );
    }

    /**
     * Invalidate sessions when account credentials change.
     */
    private function invalidateUserSessions(
        User $user,
        array $data
    ): void {
        if (isset($data['password'])) {
            $this->invalidateSessions($user);
        }
    }

    /**
     * Remove all active sessions for a user.
     */
    private function invalidateSessions(
        User $user
    ): void {
        DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
