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
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/fields';
import type {
    FieldFormData,
    FieldStatus,
    SelectOption,
    SurfaceType,
} from '@/types';

type FieldFormValues = {
    branch_id: string;
    name: string;
    surface_type: SurfaceType | '';
    size: string;
    description: string;
    status: FieldStatus;
    photo: File | null;
};

type Props = {
    branches: SelectOption<number>[];
    surfaceTypes: SelectOption<SurfaceType>[];
    statuses: SelectOption<FieldStatus>[];
    /** Terisi untuk admin — cabang dikunci ke miliknya. */
    lockedBranchId: number | null;
    field?: FieldFormData;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export function FieldForm({
    branches,
    surfaceTypes,
    statuses,
    lockedBranchId,
    field,
}: Props) {
    const form = useForm<FieldFormValues>({
        branch_id: String(field?.branch_id ?? lockedBranchId ?? ''),
        name: field?.name ?? '',
        surface_type: field?.surface_type ?? '',
        size: field?.size ?? '',
        description: field?.description ?? '',
        status: field?.status ?? 'active',
        photo: null,
    });

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        if (field) {
            // multipart tidak terbaca lewat PUT — kirim POST + method spoofing.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update(field.id).url, {
                preserveScroll: true,
                forceFormData: true,
            });

            return;
        }

        form.post(store().url, { preserveScroll: true, forceFormData: true });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Card>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="branch_id">
                            Cabang
                            <Wajib />
                        </Label>
                        <Select
                            value={form.data.branch_id}
                            onValueChange={(v) => form.setData('branch_id', v)}
                            disabled={lockedBranchId !== null}
                        >
                            <SelectTrigger
                                id="branch_id"
                                aria-invalid={Boolean(form.errors.branch_id)}
                            >
                                <SelectValue placeholder="Pilih cabang" />
                            </SelectTrigger>
                            <SelectContent>
                                {branches.map((b) => (
                                    <SelectItem
                                        key={b.value}
                                        value={String(b.value)}
                                    >
                                        {b.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {lockedBranchId !== null && (
                            <p className="text-sm text-muted-foreground">
                                Anda hanya dapat mengelola lapangan di cabang
                                Anda sendiri.
                            </p>
                        )}
                        <InputError message={form.errors.branch_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Nama Lapangan
                            <Wajib />
                        </Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Contoh: Lapangan A"
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="surface_type">
                                Tipe Rumput
                                <Wajib />
                            </Label>
                            <Select
                                value={form.data.surface_type}
                                onValueChange={(v) =>
                                    form.setData('surface_type', v as SurfaceType)
                                }
                            >
                                <SelectTrigger
                                    id="surface_type"
                                    aria-invalid={Boolean(
                                        form.errors.surface_type,
                                    )}
                                >
                                    <SelectValue placeholder="Pilih tipe rumput" />
                                </SelectTrigger>
                                <SelectContent>
                                    {surfaceTypes.map((t) => (
                                        <SelectItem key={t.value} value={t.value}>
                                            {t.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.surface_type} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="size">Ukuran</Label>
                            <Input
                                id="size"
                                value={form.data.size}
                                onChange={(e) =>
                                    form.setData('size', e.target.value)
                                }
                                placeholder="Contoh: 25x15 m"
                                aria-invalid={Boolean(form.errors.size)}
                            />
                            <InputError message={form.errors.size} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status">
                            Status
                            <Wajib />
                        </Label>
                        <Select
                            value={form.data.status}
                            onValueChange={(v) =>
                                form.setData('status', v as FieldStatus)
                            }
                        >
                            <SelectTrigger
                                id="status"
                                aria-invalid={Boolean(form.errors.status)}
                            >
                                <SelectValue placeholder="Pilih status" />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-sm text-muted-foreground">
                            Status Maintenance menyembunyikan lapangan dari
                            halaman booking publik, tapi booking yang sudah ada
                            tetap terlihat di internal.
                        </p>
                        <InputError message={form.errors.status} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            placeholder="Catatan tambahan tentang lapangan ini"
                            rows={3}
                            aria-invalid={Boolean(form.errors.description)}
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="photo">Foto Lapangan</Label>
                        {field?.photo_url && (
                            <img
                                src={field.photo_url}
                                alt={`Foto ${field.name}`}
                                className="h-32 w-full max-w-xs rounded-md object-cover"
                            />
                        )}
                        <Input
                            id="photo"
                            type="file"
                            accept="image/*"
                            onChange={(e) =>
                                form.setData('photo', e.target.files?.[0] ?? null)
                            }
                            aria-invalid={Boolean(form.errors.photo)}
                        />
                        <p className="text-sm text-muted-foreground">
                            Opsional. Maksimal 2 MB.
                            {field?.photo_url &&
                                ' Kosongkan bila tidak ingin mengganti.'}
                        </p>
                        <InputError message={form.errors.photo} />
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Harga tidak diatur di sini — atur lewat menu Harga pada
                        lapangan ini.
                    </p>
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
