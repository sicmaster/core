<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a paginated list of roles with user and permission counts.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $roles = Role::query()
            ->withCount('users', 'permissions')
            ->when(
                $search !== '',
                fn ($query) => $query->where('name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        return Inertia::render('admin/roles/create', [
            'resources' => config('permissions.resources', []),
            'actions' => config('permissions.actions', []),
            'allPermissions' => Permission::pluck('name'),
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return to_route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing an existing role.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('admin/roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'resources' => config('permissions.resources', []),
            'actions' => config('permissions.actions', []),
            'allPermissions' => Permission::pluck('name'),
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $name = $role->name === 'admin' ? 'admin' : $request->name;

        $role->update([
            'name' => $name,
        ]);

        if ($role->name === 'admin') {
            $role->syncPermissions(Permission::all());
        } else {
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }
        }

        return to_route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            abort(403, 'You cannot delete the admin role.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }

        $role->delete();

        return to_route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
