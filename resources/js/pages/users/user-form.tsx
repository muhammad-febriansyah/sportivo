import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { index, store, update } from '@/routes/users';
import type { SelectOption, UserFormData, UserRole } from '@/types';

type UserFormValues = {
    name: string;
    email: string;
    phone: string;
    role: UserRole | '';
    branch_id: string;
    is_active: boolean;
    password: string;
    password_confirmation: string;
};

type Props = {
    branches: SelectOption<number>[];
    roles: SelectOption<UserRole>[];
    /** Kosongkan untuk mode tambah. */
    user?: UserFormData;
    canDeactivate?: boolean;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export function UserForm({
    branches,
    roles,
    user,
    canDeactivate = true,
}: Props) {
    const sedangEdit = Boolean(user);

    const form = useForm<UserFormValues>({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        role: user?.role ?? '',
        branch_id: user?.branch_id ? String(user.branch_id) : '',
        is_active: user?.is_active ?? true,
        password: '',
        password_confirmation: '',
    });

    // Owner mengakses seluruh cabang, jadi tidak terikat satu cabang.
    const butuhCabang = form.data.role !== '' && form.data.role !== 'owner';

    // Submit ditangani di sini agar error validasi dari server kembali ke
    // instance useForm ini dan tampil di bawah tiap input.
    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        if (user) {
            form.put(update(user.id).url, { preserveScroll: true });

            return;
        }

        form.post(store().url, { preserveScroll: true });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Card>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Nama
                            <Wajib />
                        </Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Masukkan nama lengkap"
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">
                            Email
                            <Wajib />
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                            placeholder="Contoh: kasir@sportivo.id"
                            aria-invalid={Boolean(form.errors.email)}
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">No. WhatsApp</Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) =>
                                form.setData('phone', e.target.value)
                            }
                            placeholder="Contoh: 081234567890"
                            aria-invalid={Boolean(form.errors.phone)}
                        />
                        <InputError message={form.errors.phone} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="role">
                            Role
                            <Wajib />
                        </Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(value) =>
                                form.setData('role', value as UserRole)
                            }
                        >
                            <SelectTrigger
                                id="role"
                                aria-invalid={Boolean(form.errors.role)}
                            >
                                <SelectValue placeholder="Pilih role" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
                    </div>

                    {butuhCabang && (
                        <div className="space-y-2">
                            <Label htmlFor="branch_id">
                                Cabang
                                <Wajib />
                            </Label>
                            <Select
                                value={form.data.branch_id}
                                onValueChange={(value) =>
                                    form.setData('branch_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="branch_id"
                                    aria-invalid={Boolean(form.errors.branch_id)}
                                >
                                    <SelectValue placeholder="Pilih cabang" />
                                </SelectTrigger>
                                <SelectContent>
                                    {branches.map((branch) => (
                                        <SelectItem
                                            key={branch.value}
                                            value={String(branch.value)}
                                        >
                                            {branch.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.branch_id} />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="password">
                            Kata Sandi
                            {!sedangEdit && <Wajib />}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(e) =>
                                form.setData('password', e.target.value)
                            }
                            placeholder={
                                sedangEdit
                                    ? 'Kosongkan bila tidak ingin mengubah'
                                    : 'Masukkan kata sandi'
                            }
                            aria-invalid={Boolean(form.errors.password)}
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    {(!sedangEdit || form.data.password !== '') && (
                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">
                                Konfirmasi Kata Sandi
                                <Wajib />
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(e) =>
                                    form.setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                placeholder="Ulangi kata sandi"
                            />
                        </div>
                    )}

                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', checked)
                                }
                                disabled={!canDeactivate}
                            />
                            <Label htmlFor="is_active">Akun aktif</Label>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {canDeactivate
                                ? 'User nonaktif tidak bisa login, tapi data historisnya tetap tersimpan.'
                                : 'Anda tidak dapat menonaktifkan akun Anda sendiri.'}
                        </p>
                        <InputError message={form.errors.is_active} />
                    </div>
                </CardContent>

                <CardFooter className="justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href={index()}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
