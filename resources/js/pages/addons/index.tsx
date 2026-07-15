import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { RupiahInput } from '@/components/rupiah-input';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/format';
import { dashboard } from '@/routes';
import { destroy, store, update } from '@/routes/addons';
import type { Paginator, SelectOption } from '@/types';

type AddonRow = {
    id: number;
    branch_id: number;
    branch_name: string;
    name: string;
    price: number;
    stock: number | null;
    is_active: boolean;
};

type Props = {
    addons: Paginator<AddonRow>;
    branches: SelectOption<number>[];
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export default function AddonsIndex({ addons, branches }: Props) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const [diedit, setDiedit] = useState<AddonRow | undefined>();
    const [dihapus, setDihapus] = useState<AddonRow | undefined>();

    function bukaTambah(): void {
        setDiedit(undefined);
        setDialogTerbuka(true);
    }

    const tombolTambah = (
        <Button onClick={bukaTambah}>
            <Plus className="size-4" />
            Tambah Add-on
        </Button>
    );

    return (
        <>
            <Head title="Add-on Perlengkapan" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Add-on' },
                    ]}
                    title="Add-on Perlengkapan"
                    description="Sewa tambahan: rompi, bola, sepatu, dan lainnya."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        {branches.length > 1 && (
                                            <TableHead>Cabang</TableHead>
                                        )}
                                        <TableHead className="text-right">
                                            Harga
                                        </TableHead>
                                        <TableHead>Stok</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {addons.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="h-32 text-center"
                                            >
                                                <p className="font-medium">
                                                    Belum ada add-on
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Tambahkan perlengkapan yang
                                                    bisa disewa saat booking.
                                                </p>
                                            </TableCell>
                                        </TableRow>
                                    )}

                                    {addons.data.map((a) => (
                                        <TableRow key={a.id}>
                                            <TableCell className="font-medium">
                                                {a.name}
                                            </TableCell>
                                            {branches.length > 1 && (
                                                <TableCell>
                                                    {a.branch_name}
                                                </TableCell>
                                            )}
                                            <TableCell className="text-right">
                                                {formatRupiah(a.price)}
                                            </TableCell>
                                            <TableCell>
                                                {a.stock === null ? (
                                                    <span className="text-muted-foreground">
                                                        Tidak dibatasi
                                                    </span>
                                                ) : (
                                                    a.stock
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {a.is_active ? (
                                                    <Badge className="bg-green-600 text-white">
                                                        Aktif
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="destructive">
                                                        Nonaktif
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    onClick={() => {
                                                        setDiedit(a);
                                                        setDialogTerbuka(true);
                                                    }}
                                                >
                                                    <Pencil className="size-4" />
                                                    <span className="sr-only">
                                                        Edit {a.name}
                                                    </span>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    onClick={() =>
                                                        setDihapus(a)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-red-600" />
                                                    <span className="sr-only">
                                                        Hapus {a.name}
                                                    </span>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {addons.total > 0 && (
                            <p className="mt-4 text-sm text-muted-foreground">
                                Menampilkan {addons.from}–{addons.to} dari{' '}
                                {addons.total} add-on
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <DialogAddon
                open={dialogTerbuka}
                onOpenChange={setDialogTerbuka}
                addon={diedit}
                branches={branches}
            />

            <AlertDialog
                open={dihapus !== undefined}
                onOpenChange={(o) => !o && setDihapus(undefined)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Hapus {dihapus?.name}?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Add-on ini tidak lagi muncul saat booking baru.
                            Booking yang sudah ada tidak terpengaruh karena
                            nama dan harganya sudah tersimpan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => {
                                if (dihapus) {
                                    router.delete(destroy(dihapus.id).url, {
                                        preserveScroll: true,
                                    });
                                }

                                setDihapus(undefined);
                            }}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

/**
 * 4 field → modal, sesuai ambang di docs/04-design-system.md.
 */
function DialogAddon({
    open,
    onOpenChange,
    addon,
    branches,
}: {
    open: boolean;
    onOpenChange: (o: boolean) => void;
    addon?: AddonRow;
    branches: SelectOption<number>[];
}) {
    const form = useForm<{
        branch_id: string;
        name: string;
        price: number | '';
        stock: string;
        is_active: boolean;
    }>({
        branch_id: '',
        name: '',
        price: '',
        stock: '',
        is_active: true,
    });

    // Modal dipakai ulang antar baris; isinya harus ikut baris yang dibuka.
    useEffect(() => {
        if (!open) {
            return;
        }

        form.setDefaults({
            branch_id: String(addon?.branch_id ?? branches[0]?.value ?? ''),
            name: addon?.name ?? '',
            price: addon?.price ?? '',
            stock: addon?.stock !== null && addon?.stock !== undefined
                ? String(addon.stock)
                : '',
            is_active: addon?.is_active ?? true,
        });
        form.reset();
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, addon?.id]);

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            // Kosong berarti stok tidak dibatasi, bukan nol.
            stock: data.stock === '' ? null : data.stock,
        }));

        const opsi = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (addon) {
            form.put(update(addon.id).url, opsi);

            return;
        }

        form.post(store().url, opsi);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {addon ? 'Edit Add-on' : 'Tambah Add-on'}
                        </DialogTitle>
                        <DialogDescription>
                            Harga dan nama akan disimpan bersama booking, jadi
                            perubahan di sini tidak mengubah tagihan lama.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {branches.length > 1 && (
                            <div className="space-y-2">
                                <Label htmlFor="branch_id">
                                    Cabang
                                    <Wajib />
                                </Label>
                                <Select
                                    value={form.data.branch_id}
                                    onValueChange={(v) =>
                                        form.setData('branch_id', v)
                                    }
                                >
                                    <SelectTrigger id="branch_id">
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
                                <InputError message={form.errors.branch_id} />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="name">
                                Nama
                                <Wajib />
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="Contoh: Rompi (10 pcs)"
                                aria-invalid={Boolean(form.errors.name)}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="price">
                                Harga
                                <Wajib />
                            </Label>
                            <RupiahInput
                                id="price"
                                value={form.data.price}
                                onChange={(v) => form.setData('price', v)}
                                aria-invalid={Boolean(form.errors.price)}
                            />
                            <InputError message={form.errors.price} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="stock">Stok</Label>
                            <Input
                                id="stock"
                                type="number"
                                min={0}
                                value={form.data.stock}
                                onChange={(e) =>
                                    form.setData('stock', e.target.value)
                                }
                                placeholder="Kosongkan bila tidak dibatasi"
                                aria-invalid={Boolean(form.errors.stock)}
                            />
                            <InputError message={form.errors.stock} />
                        </div>

                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={form.data.is_active}
                                onCheckedChange={(c) =>
                                    form.setData('is_active', c)
                                }
                            />
                            <Label htmlFor="is_active">Aktif</Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
