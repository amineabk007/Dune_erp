<?php

namespace App\Http\Controllers;

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

        $roles = Role::with('permissions')->orderBy('name')->get();

        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0]);

        return view('roles.index', compact('roles', 'permissions'));
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
}
