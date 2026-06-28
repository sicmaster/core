<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a paginated list of users with optional search filtering.
     *
     * Search applies a standard LIKE query on name and email columns —
     * DB-agnostic: works on both MySQL and MariaDB (invariant #2).
     * Soft-deleted users are excluded by default via SoftDeletes (invariant #7).
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->with('roles')
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * Password is stored as plain text here; the User model cast 'password' => 'hashed'
     * handles hashing automatically (invariant: no double-hashing).
     * Role assignment uses spatie/laravel-permission (single-tenant, no tenant_id).
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $user->assignRole($request->role);

        return to_route('admin.users.index')
            ->with('success', 'User created successfully.');
    }
}
