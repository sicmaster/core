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
import { Link, router, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { useCallback, useState } from 'react';

interface Role {
    id: number;
    name: string;
}

interface UserRow {
    id: number;
    name: string;
    email: string;
    roles: Role[];
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: UserRow[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    users: PaginatedUsers;
    search: string;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function roleBadgeVariant(roleName: string): 'default' | 'secondary' | 'outline' {
    if (roleName === 'admin') return 'default';
    if (roleName === 'staff') return 'secondary';
    return 'outline';
}

export default function UserIndex({ users, search: initialSearch }: Props) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(initialSearch);

    const handleSearch = useCallback(
        (value: string) => {
            setSearch(value);
            router.get(
                admin.users.index.url(),
                { search: value || undefined },
                { preserveState: true, replace: true },
            );
        },
        [],
    );

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Users</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage system users and their roles.
                        </p>
                    </div>
                    <Button id="create-user-button" asChild>
                        <Link href={admin.users.create.url()}>
                            Create User
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <div className="flex items-center gap-4">
                    <Input
                        id="user-search"
                        type="search"
                        placeholder="Search by name or email…"
                        className="max-w-sm"
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                    />
                    {users.total > 0 && (
                        <span className="text-muted-foreground text-sm">
                            {users.from}–{users.to} of {users.total} users
                        </span>
                    )}
                </div>

                {/* Table */}
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead className="w-24">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">
                                            {user.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {user.email}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.length === 0 ? (
                                                    <Badge variant="outline">—</Badge>
                                                ) : (
                                                    user.roles.map((role) => (
                                                        <Badge
                                                            key={role.id}
                                                            variant={roleBadgeVariant(role.name)}
                                                        >
                                                            {role.name}
                                                        </Badge>
                                                    ))
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatDate(user.created_at)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    id={`edit-user-${user.id}`}
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link href={admin.users.edit.url(user)}>
                                                        Edit
                                                    </Link>
                                                </Button>

                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            id={`delete-user-${user.id}`}
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                            disabled={user.id === auth.user.id}
                                                        >
                                                            Delete
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Delete User</DialogTitle>
                                                            <DialogDescription>
                                                                Are you sure you want to delete {user.name}? This action can be undone by an administrator.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <DialogClose asChild>
                                                                <Button variant="outline">Cancel</Button>
                                                            </DialogClose>
                                                            <DialogClose asChild>
                                                                <Button
                                                                    variant="destructive"
                                                                    onClick={() => router.delete(admin.users.destroy.url(user), { preserveScroll: true })}
                                                                >
                                                                    Delete User
                                                                </Button>
                                                            </DialogClose>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {users.last_page > 1 && (
                    <nav
                        aria-label="Pagination"
                        className="flex items-center justify-center gap-1"
                    >
                        {users.links.map((link, i) => {
                            const isFirst = i === 0;
                            const isLast = i === users.links.length - 1;
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

UserIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: admin.users.index(),
        },
    ],
};
