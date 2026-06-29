import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import admin from '@/routes/admin';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    role: {
        id: number;
        name: string;
        permissions: string[];
    };
    resources: string[];
    actions: string[];
    allPermissions: string[];
}

export default function RoleEdit({ role, resources, actions, allPermissions }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: role.name,
        permissions: role.permissions,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(admin.roles.update.url(role.id));
    };

    const handlePermissionToggle = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => p !== permissionName),
            );
        }
    };

    const handleRowToggle = (resource: string, checked: boolean) => {
        const rowPermissions = actions
            .map((action) => `${resource}.${action}`)
            .filter((p) => allPermissions.includes(p));

        if (checked) {
            const newPermissions = new Set([...data.permissions, ...rowPermissions]);
            setData('permissions', Array.from(newPermissions));
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => !rowPermissions.includes(p)),
            );
        }
    };

    const handleColumnToggle = (action: string, checked: boolean) => {
        const colPermissions = resources
            .map((resource) => `${resource}.${action}`)
            .filter((p) => allPermissions.includes(p));

        if (checked) {
            const newPermissions = new Set([...data.permissions, ...colPermissions]);
            setData('permissions', Array.from(newPermissions));
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => !colPermissions.includes(p)),
            );
        }
    };

    // Separate standalone permissions (like 'access admin') from resource permissions
    const resourcePermissions = resources.flatMap((r) =>
        actions.map((a) => `${r}.${a}`),
    );
    const standalonePermissions = allPermissions.filter(
        (p) => !resourcePermissions.includes(p),
    );

    const isAdmin = role.name === 'admin';

    return (
        <>
            <Head title={`Edit Role: ${role.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Edit Role</h1>
                        <p className="text-muted-foreground text-sm">
                            Update role information and manage its permissions.
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6 max-w-4xl">
                    <div className="grid gap-4 rounded-lg border p-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Role Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="e.g. Manager"
                                autoFocus
                                disabled={isAdmin}
                            />
                            {isAdmin && (
                                <p className="text-muted-foreground text-xs">
                                    The 'admin' role name cannot be changed.
                                </p>
                            )}
                            {errors.name && (
                                <p className="text-destructive text-sm">{errors.name}</p>
                            )}
                        </div>

                        <div className="grid gap-2 mt-4">
                            <Label>Permissions Matrix</Label>
                            <p className="text-muted-foreground text-sm mb-2">
                                Check the boxes below to grant access.
                            </p>
                            
                            <div className="rounded-md border overflow-hidden">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[200px]">Resource</TableHead>
                                            {actions.map((action) => {
                                                const colPermissions = resources
                                                    .map((resource) => `${resource}.${action}`)
                                                    .filter((p) => allPermissions.includes(p));
                                                
                                                const isAllChecked = colPermissions.length > 0 && colPermissions.every(p => data.permissions.includes(p));
                                                
                                                return (
                                                    <TableHead key={action} className="text-center">
                                                        <div className="flex flex-col items-center gap-2">
                                                            <span className="capitalize">{action}</span>
                                                            <Checkbox 
                                                                checked={isAllChecked}
                                                                onCheckedChange={(checked) => handleColumnToggle(action, checked as boolean)}
                                                            />
                                                        </div>
                                                    </TableHead>
                                                );
                                            })}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {resources.map((resource) => {
                                            const rowPermissions = actions
                                                .map((action) => `${resource}.${action}`)
                                                .filter((p) => allPermissions.includes(p));
                                            
                                            const isAllChecked = rowPermissions.length > 0 && rowPermissions.every(p => data.permissions.includes(p));

                                            return (
                                                <TableRow key={resource}>
                                                    <TableCell className="font-medium">
                                                        <div className="flex items-center gap-3">
                                                            <Checkbox 
                                                                checked={isAllChecked}
                                                                onCheckedChange={(checked) => handleRowToggle(resource, checked as boolean)}
                                                            />
                                                            <span className="capitalize">{resource}</span>
                                                        </div>
                                                    </TableCell>
                                                    {actions.map((action) => {
                                                        const permissionName = `${resource}.${action}`;
                                                        const exists = allPermissions.includes(permissionName);
                                                        
                                                        return (
                                                            <TableCell key={action} className="text-center">
                                                                {exists ? (
                                                                    <Checkbox
                                                                        checked={data.permissions.includes(permissionName)}
                                                                        onCheckedChange={(checked) =>
                                                                            handlePermissionToggle(permissionName, checked as boolean)
                                                                        }
                                                                    />
                                                                ) : (
                                                                    <span className="text-muted-foreground text-xs">-</span>
                                                                )}
                                                            </TableCell>
                                                        );
                                                    })}
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                            {errors.permissions && (
                                <p className="text-destructive text-sm">{errors.permissions}</p>
                            )}
                        </div>

                        {standalonePermissions.length > 0 && (
                            <div className="grid gap-2 mt-4">
                                <Label>Other Permissions</Label>
                                <div className="flex flex-wrap gap-4 mt-2">
                                    {standalonePermissions.map((permissionName) => (
                                        <div key={permissionName} className="flex items-center gap-2">
                                            <Checkbox
                                                id={`perm-${permissionName}`}
                                                checked={data.permissions.includes(permissionName)}
                                                onCheckedChange={(checked) =>
                                                    handlePermissionToggle(permissionName, checked as boolean)
                                                }
                                            />
                                            <Label htmlFor={`perm-${permissionName}`} className="cursor-pointer font-normal">
                                                {permissionName}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Save Changes
                        </Button>
                        <Button type="button" variant="ghost" asChild>
                            <Link href={admin.roles.index.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

RoleEdit.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: admin.roles.index(),
        },
        {
            title: 'Edit',
            href: '',
        },
    ],
};
