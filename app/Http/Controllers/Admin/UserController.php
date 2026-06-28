<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
