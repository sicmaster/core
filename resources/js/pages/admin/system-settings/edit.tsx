import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePermissions } from '@/hooks/use-permissions';
import admin from '@/routes/admin';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    settings: {
        site_name: string;
        site_description: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        enabled_locales: string[];
        default_locale: string;
    };
    supportedLocales: Record<string, string>;
}

export default function EditSystemSettings({ settings, supportedLocales }: Props) {
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission('settings.update');

    const { data, setData, put, processing, errors } = useForm({
        site_name: settings.site_name || '',
        site_description: settings.site_description || '',
        contact_email: settings.contact_email || '',
        contact_phone: settings.contact_phone || '',
        enabled_locales: settings.enabled_locales || ['th'],
        default_locale: settings.default_locale || 'th',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(admin.systemSettings.update.url());
    };

    const handleLocaleToggle = (localeCode: string, checked: boolean) => {
        let newLocales = [...data.enabled_locales];
        if (checked) {
            if (!newLocales.includes(localeCode)) {
                newLocales.push(localeCode);
            }
        } else {
            newLocales = newLocales.filter((l) => l !== localeCode);
        }

        // If the default_locale was unchecked, reset default_locale to the first available if any
        let newDefault = data.default_locale;
        if (!newLocales.includes(newDefault)) {
            newDefault = newLocales.length > 0 ? newLocales[0] : '';
        }

        setData((prev) => ({
            ...prev,
            enabled_locales: newLocales,
            default_locale: newDefault,
        }));
    };

    return (
        <>
            <Head title="System Settings" />

            <div className="flex flex-1 flex-col gap-4 p-4 lg:p-6 lg:gap-6">
                <div>
                    <h1 className="text-2xl font-semibold">System Settings</h1>
                    <p className="text-muted-foreground text-sm">
                        Manage global configuration and locale preferences.
                    </p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6 max-w-2xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>General</CardTitle>
                            <CardDescription>Basic information about your site.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="site_name">Site Name</Label>
                                <Input
                                    id="site_name"
                                    value={data.site_name}
                                    onChange={(e) => setData('site_name', e.target.value)}
                                    disabled={!canUpdate}
                                />
                                {errors.site_name && (
                                    <p className="text-destructive text-sm">{errors.site_name}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="site_description">Site Description</Label>
                                <Input
                                    id="site_description"
                                    value={data.site_description}
                                    onChange={(e) => setData('site_description', e.target.value)}
                                    disabled={!canUpdate}
                                />
                                {errors.site_description && (
                                    <p className="text-destructive text-sm">{errors.site_description}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">Contact Email</Label>
                                    <Input
                                        id="contact_email"
                                        type="email"
                                        value={data.contact_email}
                                        onChange={(e) => setData('contact_email', e.target.value)}
                                        disabled={!canUpdate}
                                    />
                                    {errors.contact_email && (
                                        <p className="text-destructive text-sm">{errors.contact_email}</p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">Contact Phone</Label>
                                    <Input
                                        id="contact_phone"
                                        value={data.contact_phone}
                                        onChange={(e) => setData('contact_phone', e.target.value)}
                                        disabled={!canUpdate}
                                    />
                                    {errors.contact_phone && (
                                        <p className="text-destructive text-sm">{errors.contact_phone}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Locales & Language</CardTitle>
                            <CardDescription>Configure which languages are available on the frontend.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-6">
                            <div className="grid gap-3">
                                <Label>Enabled Locales</Label>
                                <div className="flex flex-col gap-2">
                                    {Object.entries(supportedLocales).map(([code, name]) => (
                                        <div key={code} className="flex items-center space-x-2">
                                            <Checkbox
                                                id={`locale-${code}`}
                                                checked={data.enabled_locales.includes(code)}
                                                onCheckedChange={(checked) => handleLocaleToggle(code, checked as boolean)}
                                                disabled={!canUpdate}
                                            />
                                            <Label
                                                htmlFor={`locale-${code}`}
                                                className="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                            >
                                                {name} ({code})
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                {errors.enabled_locales && (
                                    <p className="text-destructive text-sm">{errors.enabled_locales}</p>
                                )}
                                {Object.keys(errors).some(k => k.startsWith('enabled_locales.')) && (
                                    <p className="text-destructive text-sm">One or more selected locales are invalid.</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="default_locale">Default Locale</Label>
                                <Select
                                    value={data.default_locale}
                                    onValueChange={(val) => setData('default_locale', val)}
                                    disabled={!canUpdate || data.enabled_locales.length === 0}
                                >
                                    <SelectTrigger id="default_locale" className="w-[200px]">
                                        <SelectValue placeholder="Select default language" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {data.enabled_locales.map((code) => (
                                            <SelectItem key={code} value={code}>
                                                {supportedLocales[code] || code}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.default_locale && (
                                    <p className="text-destructive text-sm">{errors.default_locale}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {canUpdate && (
                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                Save Changes
                            </Button>
                        </div>
                    )}
                </form>
            </div>
        </>
    );
}

EditSystemSettings.layout = {
    breadcrumbs: [
        {
            title: 'System Settings',
            href: admin.systemSettings.edit(),
        },
    ],
};
