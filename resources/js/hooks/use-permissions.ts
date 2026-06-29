import { usePage } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage().props as any;

    const hasPermission = (permission: string) => {
        if (!auth?.user?.permissions) {
            return false;
        }
        
        // If they have all permissions explicitly (from seeder)
        return auth.user.permissions.includes(permission);
    };

    const hasAnyPermission = (permissions: string[]) => {
        if (!auth?.user?.permissions) {
            return false;
        }

        return permissions.some((permission) => 
            auth.user.permissions.includes(permission)
        );
    };

    const hasAllPermissions = (permissions: string[]) => {
        if (!auth?.user?.permissions) {
            return false;
        }

        return permissions.every((permission) => 
            auth.user.permissions.includes(permission)
        );
    };

    return {
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        permissions: auth?.user?.permissions || [],
    };
}
