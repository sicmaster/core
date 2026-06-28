import { Head } from '@inertiajs/react';
import admin from '@/routes/admin';

export default function AdminDashboard() {
    return (
        <>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Admin Dashboard</h1>
                <p className="text-muted-foreground">
                    Welcome to the admin panel.
                </p>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: admin.dashboard(),
        },
    ],
};
