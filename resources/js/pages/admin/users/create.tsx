import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';
import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';

interface Props {
    roles: string[];
}

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
}

export default function UserCreate({ roles }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(admin.users.store.url());
    }

    return (
        <>
            <Head title="Create User" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-semibold">Create User</h1>
                    <p className="text-muted-foreground text-sm">
                        Add a new user and assign a role.
                    </p>
                </div>

                {/* Form */}
                <form
                    id="create-user-form"
                    onSubmit={handleSubmit}
                    className="max-w-md space-y-5"
                >
                    {/* Name */}
                    <div className="space-y-1.5">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            aria-invalid={!!errors.name}
                        />
                        {errors.name && (
                            <p className="text-destructive text-sm">{errors.name}</p>
                        )}
                    </div>

                    {/* Email */}
                    <div className="space-y-1.5">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            aria-invalid={!!errors.email}
                        />
                        {errors.email && (
                            <p className="text-destructive text-sm">{errors.email}</p>
                        )}
                    </div>

                    {/* Password */}
                    <div className="space-y-1.5">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            aria-invalid={!!errors.password}
                        />
                        {errors.password && (
                            <p className="text-destructive text-sm">{errors.password}</p>
                        )}
                    </div>

                    {/* Password Confirmation */}
                    <div className="space-y-1.5">
                        <Label htmlFor="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            aria-invalid={!!errors.password_confirmation}
                        />
                        {errors.password_confirmation && (
                            <p className="text-destructive text-sm">
                                {errors.password_confirmation}
                            </p>
                        )}
                    </div>

                    {/* Role */}
                    <div className="space-y-1.5">
                        <Label htmlFor="role">Role</Label>
                        <Select
                            value={data.role}
                            onValueChange={(value) => setData('role', value)}
                        >
                            <SelectTrigger
                                id="role"
                                className="w-full"
                                aria-invalid={!!errors.role}
                            >
                                <SelectValue placeholder="Select a role…" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {role}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.role && (
                            <p className="text-destructive text-sm">{errors.role}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3 pt-2">
                        <Button
                            id="submit-create-user"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? 'Creating…' : 'Create User'}
                        </Button>
                        <Button id="cancel-create-user" asChild variant="outline">
                            <Link href={admin.users.index.url()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

UserCreate.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: admin.dashboard(),
        },
        {
            title: 'Users',
            href: admin.users.index(),
        },
        {
            title: 'Create User',
            href: admin.users.create(),
        },
    ],
};
