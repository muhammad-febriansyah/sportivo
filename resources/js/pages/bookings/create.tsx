import { Head, Link, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CustomerSearch } from '@/components/customer-search';
import { DatePickerField } from '@/components/date-picker-field';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
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
import { dashboard } from '@/routes';
import { grid, store } from '@/routes/bookings';
import { quickStore } from '@/routes/customers';
import type { CustomerSearchResult } from '@/types';

type Props = {
    prefill: {
        field_id: number | null;
        booking_date: string;
        start_time: string | null;
    };
    fields: { value: number; label: string; branch_id: number }[];
};

type BookingFormValues = {
    field_id: string;
    customer_id: string;
    booking_date: string;
    start_time: string;
    duration_hours: string;
    pay_full: boolean;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export default function BookingsCreate({ prefill, fields }: Props) {
    const [customer, setCustomer] = useState<CustomerSearchResult | null>(null);
    const [dialogBaru, setDialogBaru] = useState(false);

    const form = useForm<BookingFormValues>({
        field_id: prefill.field_id ? String(prefill.field_id) : '',
        customer_id: '',
        booking_date: prefill.booking_date,
        start_time: prefill.start_time ?? '19:00',
        duration_hours: '1',
        pay_full: false,
    });

    function pilihCustomer(c: CustomerSearchResult | null): void {
        setCustomer(c);
        form.setData('customer_id', c ? String(c.id) : '');
    }

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        form.post(store().url, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Booking Walk-in" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Grid Booking', href: grid() },
                        { title: 'Booking Baru' },
                    ]}
                    title="Booking Walk-in"
                    description="Harga dihitung otomatis dari aturan harga lapangan."
                />

                <form onSubmit={handleSubmit} className="max-w-2xl">
                    <Card>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="field_id">
                                    Lapangan
                                    <Wajib />
                                </Label>
                                <Select
                                    value={form.data.field_id}
                                    onValueChange={(v) =>
                                        form.setData('field_id', v)
                                    }
                                >
                                    <SelectTrigger
                                        id="field_id"
                                        aria-invalid={Boolean(
                                            form.errors.field_id,
                                        )}
                                    >
                                        <SelectValue placeholder="Pilih lapangan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {fields.map((f) => (
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

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="customer_search">
                                        Pelanggan
                                        <Wajib />
                                    </Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setDialogBaru(true)}
                                    >
                                        <UserPlus className="size-4" />
                                        Pelanggan baru
                                    </Button>
                                </div>
                                <CustomerSearch
                                    value={customer}
                                    onSelect={pilihCustomer}
                                    aria-invalid={Boolean(
                                        form.errors.customer_id,
                                    )}
                                />
                                <InputError message={form.errors.customer_id} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <DatePickerField
                                    id="booking_date"
                                    label="Tanggal"
                                    value={form.data.booking_date}
                                    onChange={(v) =>
                                        form.setData('booking_date', v)
                                    }
                                    required
                                    disablePastDates
                                    error={form.errors.booking_date}
                                />

                                <div className="space-y-2">
                                    <Label htmlFor="start_time">
                                        Jam Mulai
                                        <Wajib />
                                    </Label>
                                    <Input
                                        id="start_time"
                                        type="time"
                                        step={3600}
                                        value={form.data.start_time}
                                        onChange={(e) =>
                                            form.setData(
                                                'start_time',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={Boolean(
                                            form.errors.start_time,
                                        )}
                                    />
                                    <InputError
                                        message={form.errors.start_time}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="duration_hours">
                                        Durasi (jam)
                                        <Wajib />
                                    </Label>
                                    <Input
                                        id="duration_hours"
                                        type="number"
                                        min={1}
                                        max={12}
                                        value={form.data.duration_hours}
                                        onChange={(e) =>
                                            form.setData(
                                                'duration_hours',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={Boolean(
                                            form.errors.duration_hours,
                                        )}
                                    />
                                    <InputError
                                        message={form.errors.duration_hours}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <Switch
                                        id="pay_full"
                                        checked={form.data.pay_full}
                                        onCheckedChange={(c) =>
                                            form.setData('pay_full', c)
                                        }
                                    />
                                    <Label htmlFor="pay_full">
                                        Bayar lunas
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Bila dimatikan, DP mengikuti persentase yang
                                    diatur di cabang.
                                </p>
                            </div>

                            <p className="text-sm text-muted-foreground">
                                Harga tidak diinput manual — dihitung server
                                dari aturan harga lapangan, tanggal, dan status
                                member pelanggan.
                            </p>
                        </CardContent>

                        <CardFooter className="justify-end gap-2">
                            <Button variant="outline" asChild>
                                <Link href={grid()}>Batal</Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                Simpan
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>

            <DialogPelangganBaru
                open={dialogBaru}
                onOpenChange={setDialogBaru}
                onCreated={(c) => {
                    pilihCustomer(c);
                    setDialogBaru(false);
                }}
            />
        </>
    );
}

/**
 * Hanya nama + nomor WA (US-05) — 2 field, jadi modal sesuai
 * docs/04-design-system.md bagian Form Rules.
 */
function DialogPelangganBaru({
    open,
    onOpenChange,
    onCreated,
}: {
    open: boolean;
    onOpenChange: (o: boolean) => void;
    onCreated: (c: CustomerSearchResult) => void;
}) {
    const [nama, setNama] = useState('');
    const [telepon, setTelepon] = useState('');
    const [proses, setProses] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function simpan(): Promise<void> {
        setProses(true);
        setError(null);

        try {
            const res = await fetch(quickStore().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name: nama, phone: telepon }),
            });

            if (!res.ok) {
                const data = await res.json().catch(() => null);
                setError(
                    data?.errors?.phone?.[0] ??
                        data?.errors?.name?.[0] ??
                        'Gagal menyimpan pelanggan.',
                );

                return;
            }

            const c: CustomerSearchResult = await res.json();
            onCreated(c);
            setNama('');
            setTelepon('');
        } finally {
            setProses(false);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Pelanggan Baru</DialogTitle>
                    <DialogDescription>
                        Cukup nama dan nomor WhatsApp. Data lain bisa
                        dilengkapi nanti di halaman pelanggan.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div className="space-y-2">
                        <Label htmlFor="nama_baru">
                            Nama
                            <Wajib />
                        </Label>
                        <Input
                            id="nama_baru"
                            value={nama}
                            onChange={(e) => setNama(e.target.value)}
                            placeholder="Masukkan nama pelanggan"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="telepon_baru">
                            Nomor WhatsApp
                            <Wajib />
                        </Label>
                        <Input
                            id="telepon_baru"
                            value={telepon}
                            onChange={(e) => setTelepon(e.target.value)}
                            placeholder="Contoh: 081234567890"
                        />
                    </div>

                    {error && <p className="text-sm text-red-600">{error}</p>}
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        onClick={simpan}
                        disabled={proses || !nama || !telepon}
                    >
                        {proses && <Spinner />}
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
