<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * Returns different root view based on route:
     * - 'admin.*' routes → 'admin' (prepared for future separation)
     * - other routes → 'app'
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    public function rootView(Request $request): string
    {
        // Invariant #4: แยกหน้าบ้าน/หลังบ้าน
        // ตอนนี้ยังใช้ 'app' เดียว แต่วาง logic เตรียมแยกไว้แล้ว
        return $request->routeIs('admin.*') ? 'app' : 'app';
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
