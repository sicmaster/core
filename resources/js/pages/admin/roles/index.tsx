import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import admin from '@/routes/admin';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { usePermissions } from '@/hooks/use-permissions';

interface RoleRow {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRoles {
    data: RoleRow[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    roles: PaginatedRoles;
    search: string;
}

export default function RoleIndex({ roles, search: initialSearch }: Props) {
    const { hasPermission } = usePermissions();
    const [search, setSearch] = useState(initialSearch);

    const handleSearch = useCallback(
        (value: string) => {
            setSearch(value);
            router.get(
                admin.roles.index.url(),
                { search: value || undefined },
                { preserveState: true, replace: true },
            );
        },
        [],
    );

    return (
        <>
            <Head title="Roles" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Roles</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage user roles and permissions.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {hasPermission('roles.read') && (
                            <Button id="export-roles-button" variant="outline" asChild>
                                <a href={admin.roles.export.url()} target="_blank" rel="noreferrer">
                                    Export CSV
                                </a>
                            </Button>
                        )}
                        {hasPermission('roles.create') && (
                            <Button id="create-role-button" asChild>
                                <Link href={admin.roles.create.url()}>
                                    Create Role
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Search */}
                <div className="flex items-center gap-4">
                    <Input
                        id="role-search"
                        type="search"
                        placeholder="Search by name…"
                        className="max-w-sm"
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                    />
                    {roles.total > 0 && (
                        <span className="text-muted-foreground text-sm">
                            {roles.from}–{roles.to} of {roles.total} roles
                        </span>
                    )}
                </div>

                {/* Table */}
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead className="w-24">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No roles found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                roles.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="font-medium">
                                            {role.name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {role.users_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {role.permissions_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                {hasPermission('roles.update') && (
                                                    <Button
                                                        id={`edit-role-${role.id}`}
                                                        asChild
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Link href={admin.roles.edit.url(role)}>
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                )}

                                                {hasPermission('roles.delete') && (
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                id={`delete-role-${role.id}`}
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                                disabled={role.name === 'admin' || role.users_count > 0}
                                                            >
                                                                Delete
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                <DialogTitle>Delete Role</DialogTitle>
                                                                <DialogDescription>
                                                                    Are you sure you want to delete the '{role.name}' role? This action cannot be undone.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <DialogFooter>
                                                                <DialogClose asChild>
                                                                    <Button variant="outline">Cancel</Button>
                                                                </DialogClose>
                                                                <DialogClose asChild>
                                                                    <Button
                                                                        variant="destructive"
                                                                        onClick={() => router.delete(admin.roles.destroy.url(role), { preserveScroll: true })}
                                                                    >
                                                                        Delete Role
                                                                    </Button>
                                                                </DialogClose>
                                                            </DialogFooter>
                                                        </DialogContent>
                                                    </Dialog>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {roles.last_page > 1 && (
                    <nav
                        aria-label="Pagination"
                        className="flex items-center justify-center gap-1"
                    >
                        {roles.links.map((link, i) => {
                            const isFirst = i === 0;
                            const isLast = i === roles.links.length - 1;
                            const isPrev = isFirst;
                            const isNext = isLast;

                            return (
                                <button
                                    key={i}
                                    id={
                                        isPrev
                                            ? 'pagination-prev'
                                            : isNext
                                              ? 'pagination-next'
                                              : `pagination-page-${link.label}`
                                    }
                                    disabled={link.url === null}
                                    aria-current={link.active ? 'page' : undefined}
                                    className={[
                                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors',
                                        link.active
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'border-border hover:bg-accent',
                                        link.url === null
                                            ? 'cursor-not-allowed opacity-40'
                                            : 'cursor-pointer',
                                    ].join(' ')}
                                    onClick={() => {
                                        if (link.url) {
                                            router.get(link.url, {}, { preserveState: true });
                                        }
                                    }}
                                    // eslint-disable-next-line react/no-danger
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </nav>
                )}
            </div>
        </>
    );
}

RoleIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: admin.roles.index(),
        },
    ],
};
