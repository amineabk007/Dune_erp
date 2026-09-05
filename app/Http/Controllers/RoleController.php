<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Visual matrix of every role against every permission, grouped by module.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0]);

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0]);

        return view('roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->string('name')->toString(), 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        $this->audit->log('create', 'roles', $role, null, [
            'name' => $role->name,
            'permissions' => $request->input('permissions', []),
        ]);

        return redirect()->route('roles.index')->with('status', "Rôle « {$role->name} » créé.");
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return back()->with('status', "Le rôle admin conserve toujours l'accès global.");
        }

        $old = $role->permissions()->pluck('name')->all();
        $role->syncPermissions($request->input('permissions', []));

        $this->audit->log(
            'update',
            'roles',
            $role,
            ['permissions' => $old],
            ['permissions' => $request->input('permissions', [])]
        );

        return redirect()->route('roles.index')->with('status', "Permissions du rôle « {$role->name} » mises à jour.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        // These are safety rules, not permissions: they must apply even to
        // admin, which the Gate::before bypass would otherwise skip if left
        // inside the policy (the same reasoning as the self-deactivation
        // guard in UserController::toggleActive()).
        abort_if($role->name === 'admin', 403, 'Le rôle admin ne peut pas être supprimé.');
        abort_if($role->users()->exists(), 403, 'Ce rôle est encore assigné à au moins un utilisateur.');

        $this->audit->log('delete', 'roles', $role, ['name' => $role->name], null);
        $role->delete();

        return redirect()->route('roles.index')->with('status', "Rôle « {$role->name} » supprimé.");
    }
}
