<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export users to CSV.
     */
    public function exportUsers(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $file = fopen('php://output', 'w');
            
            // Write BOM for Excel UTF-8 compatibility
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Roles', 'Created At']);

            User::with('roles')->chunk(100, function ($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->roles->pluck('name')->join(', '),
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export roles to CSV.
     */
    public function exportRoles(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="roles.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $file = fopen('php://output', 'w');
            
            // Write BOM for Excel UTF-8 compatibility
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Users Count', 'Permissions']);

            Role::with('permissions')->withCount('users')->chunk(100, function ($roles) use ($file) {
                foreach ($roles as $role) {
                    fputcsv($file, [
                        $role->id,
                        $role->name,
                        $role->users_count,
                        $role->permissions->pluck('name')->join(', '),
                    ]);
                }
            });

            fclose($file);
        }, 200, $headers);
    }
}
