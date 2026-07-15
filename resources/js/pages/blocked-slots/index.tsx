import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DatePickerField } from '@/components/date-picker-field';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatTanggal } from '@/lib/format';
import { dashboard } from '@/routes';
import { destroy, store } from '@/routes/blocked-slots';
import type { Paginator, SelectOption } from '@/types';

type BlockRow = {
    id: number;
    branch_name: string;
    field_name: string | null;
    block_date: string;
    start_time: string;
    end_time: string;
    reason: string;
    created_by_name: string | null;
};

type Props = {
    blocks: Paginator<BlockRow>;
    branches: SelectOption<number>[];
    fields: { value: number; label: string; branch_id: number }[];
};

const SEMUA_LAPANGAN = 'semua';

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export default function BlockedSlotsIndex({ blocks, branches, fields }: Props) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const [dihapus, setDihapus] = useState<BlockRow | undefined>();

    const tombolTambah = (
        <Button onClick={() => setDialogTerbuka(true)}>
            <Plus className="size-4" />
            Blokir Slot
        </Button>
    );

    return (
        <>
            <Head title="Blocking Slot" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Blocking Slot' },
                    ]}
                    title="Blocking Slot"
                    description="Tutup slot untuk maintenance atau event privat."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead>Jam</TableHead>
                                        <TableHead>Cabang</TableHead>
                                        <TableHead>Lapangan</TableHead>
                                        <TableHead>Alasan</TableHead>
                                        <TableHead>Dibuat oleh</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {blocks.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="h-32 text-center"
                                            >
                                                <p className="font-medium">
                                                    Tidak ada blokir aktif
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Blokir yang tanggalnya sudah
                                                    lewat tidak ditampilkan.
                                                </p>
                                            </TableCell>
                                        </TableRow>
                                    )}

                                    {blocks.data.map((b) => (
                                        <TableRow key={b.id}>
                                            <TableCell>
                                                {formatTanggal(b.block_date)}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {b.start_time}–{b.end_time}
                                            </TableCell>
                                            <TableCell>
                                                {b.branch_name}
                                            </TableCell>
                                            <TableCell>
                                                {b.field_name ?? (
                                                    <Badge variant="secondary">
                                                        Semua lapangan
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>{b.reason}</TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {b.created_by_name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    onClick={() =>
                                                        setDihapus(b)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-red-600" />
                                                    <span className="sr-only">
                                                        Hapus blokir
                                                    </span>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {blocks.total > 0 && (
                            <p className="mt-4 text-sm text-muted-foreground">
                                Menampilkan {blocks.from}–{blocks.to} dari{' '}
                                {blocks.total} blokir
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <DialogBlokir
                open={dialogTerbuka}
                onOpenChange={setDialogTerbuka}
                branches={branches}
                fields={fields}
            />

            <AlertDialog
                open={dihapus !== undefined}
                onOpenChange={(o) => !o && setDihapus(undefined)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus blokir ini?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Slot {dihapus?.start_time}–{dihapus?.end_time} pada{' '}
                            {dihapus && formatTanggal(dihapus.block_date)} akan
                            kembali bisa dibooking.
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
 * 5 field → modal, sesuai ambang di docs/04-design-system.md.
 */
function DialogBlokir({
    open,
    onOpenChange,
    branches,
    fields,
}: {
    open: boolean;
    onOpenChange: (o: boolean) => void;
    branches: SelectOption<number>[];
    fields: { value: number; label: string; branch_id: number }[];
}) {
    const form = useForm({
        branch_id: branches.length === 1 ? String(branches[0].value) : '',
        field_id: SEMUA_LAPANGAN,
        block_date: '',
        start_time: '08:00',
        end_time: '12:00',
        reason: '',
    });

    // Hanya lapangan milik cabang terpilih yang relevan.
    const lapanganCabang = fields.filter(
        (f) => String(f.branch_id) === form.data.branch_id,
    );

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            // "semua" bukan id lapangan — kirim null agar seluruh cabang tertutup.
            field_id: data.field_id === SEMUA_LAPANGAN ? null : data.field_id,
        }));

        form.post(store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Blokir Slot</DialogTitle>
                        <DialogDescription>
                            Slot yang sudah punya booking aktif tidak bisa
                            diblokir.
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
                                    onValueChange={(v) => {
                                        form.setData('branch_id', v);
                                        form.setData(
                                            'field_id',
                                            SEMUA_LAPANGAN,
                                        );
                                    }}
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
                            <Label htmlFor="field_id">Lapangan</Label>
                            <Select
                                value={form.data.field_id}
                                onValueChange={(v) =>
                                    form.setData('field_id', v)
                                }
                                disabled={!form.data.branch_id}
                            >
                                <SelectTrigger id="field_id">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={SEMUA_LAPANGAN}>
                                        Semua lapangan di cabang ini
                                    </SelectItem>
                                    {lapanganCabang.map((f) => (
                                        <SelectItem
                                            key={f.value}
                                            value={String(f.value)}
                                        >
                                            {f.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.field_id} />
                        </div>

                        <DatePickerField
                            id="block_date"
                            label="Tanggal"
                            value={form.data.block_date}
                            onChange={(v) => form.setData('block_date', v)}
                            required
                            disablePastDates
                            error={form.errors.block_date}
                        />

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="start_time">
                                    Jam Mulai
                                    <Wajib />
                                </Label>
                                <Input
                                    id="start_time"
                                    type="time"
                                    value={form.data.start_time}
                                    onChange={(e) =>
                                        form.setData(
                                            'start_time',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.start_time} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="end_time">
                                    Jam Selesai
                                    <Wajib />
                                </Label>
                                <Input
                                    id="end_time"
                                    type="time"
                                    value={form.data.end_time}
                                    onChange={(e) =>
                                        form.setData('end_time', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.end_time} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="reason">
                                Alasan
                                <Wajib />
                            </Label>
                            <Input
                                id="reason"
                                value={form.data.reason}
                                onChange={(e) =>
                                    form.setData('reason', e.target.value)
                                }
                                placeholder="Contoh: Maintenance rumput"
                            />
                            <InputError message={form.errors.reason} />
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
                            Blokir
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
