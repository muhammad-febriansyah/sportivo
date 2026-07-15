import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { FormEvent } from 'react';
import { DatePickerField } from '@/components/date-picker-field';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
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
import { formatRupiah, formatTanggal } from '@/lib/format';
import { dashboard } from '@/routes';
import { index, reschedule, show } from '@/routes/bookings';
import type { SelectOption } from '@/types';

type Props = {
    booking: {
        id: number;
        code: string;
        field_id: number;
        field_name: string;
        booking_date: string;
        start_time: string;
        end_time: string;
        duration_hours: number;
        price_per_hour: number;
        total: number;
        paid_amount: number;
        reschedule_count: number;
    };
    canReschedule: boolean;
    reason: string | null;
    fields: SelectOption<number>[];
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export default function BookingsReschedule({
    booking,
    canReschedule,
    reason,
    fields,
}: Props) {
    const form = useForm({
        booking_date: booking.booking_date,
        start_time: booking.start_time,
        field_id: String(booking.field_id),
    });

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        form.put(reschedule(booking.id).url, { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Reschedule ${booking.code}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Booking', href: index() },
                        { title: booking.code, href: show(booking.id) },
                        { title: 'Reschedule' },
                    ]}
                    title="Reschedule Booking"
                    description={`Jadwal sekarang: ${formatTanggal(booking.booking_date)}, ${booking.start_time}–${booking.end_time} di ${booking.field_name}.`}
                />

                {!canReschedule && (
                    <div className="mb-4 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 p-4">
                        <AlertTriangle className="mt-0.5 size-5 shrink-0 text-red-600" />
                        <div className="text-sm">
                            <p className="font-medium text-red-900">
                                Booking ini tidak bisa di-reschedule
                            </p>
                            <p className="text-red-800">{reason}</p>
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="max-w-2xl">
                    <Card>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="field_id">Lapangan</Label>
                                <Select
                                    value={form.data.field_id}
                                    onValueChange={(v) =>
                                        form.setData('field_id', v)
                                    }
                                    disabled={!canReschedule}
                                >
                                    <SelectTrigger id="field_id">
                                        <SelectValue />
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
                                <p className="text-sm text-muted-foreground">
                                    Hanya lapangan di cabang yang sama.
                                </p>
                                <InputError message={form.errors.field_id} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <DatePickerField
                                    id="booking_date"
                                    label="Tanggal Baru"
                                    value={form.data.booking_date}
                                    onChange={(v) =>
                                        form.setData('booking_date', v)
                                    }
                                    required
                                    disablePastDates
                                    disabled={!canReschedule}
                                    error={form.errors.booking_date}
                                />

                                <div className="space-y-2">
                                    <Label htmlFor="start_time">
                                        Jam Mulai Baru
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
                                        disabled={!canReschedule}
                                        aria-invalid={Boolean(
                                            form.errors.start_time,
                                        )}
                                    />
                                    <InputError
                                        message={form.errors.start_time}
                                    />
                                </div>
                            </div>

                            <div className="rounded-md border bg-muted/40 p-4 text-sm">
                                <p className="font-medium">
                                    Durasi tetap {booking.duration_hours} jam.
                                </p>
                                <p className="mt-1 text-muted-foreground">
                                    Harga dihitung ulang dari aturan harga slot
                                    baru. Bila lebih mahal, selisihnya menambah
                                    tagihan; bila lebih murah, tagihan berkurang.
                                    Tagihan saat ini{' '}
                                    {formatRupiah(booking.total)}, sudah dibayar{' '}
                                    {formatRupiah(booking.paid_amount)}.
                                </p>
                            </div>
                        </CardContent>

                        <CardFooter className="justify-end gap-2">
                            <Button variant="outline" asChild>
                                <Link href={show(booking.id)}>Batal</Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing || !canReschedule}
                            >
                                {form.processing && <Spinner />}
                                Pindahkan Jadwal
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
