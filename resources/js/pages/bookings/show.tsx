import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatRupiah,
    formatStatusBooking,
    formatTanggal,
    formatWaktuWib,
    warnaStatusBooking,
} from '@/lib/format';
import { dashboard } from '@/routes';
import { cancel, checkIn, grid, index } from '@/routes/bookings';
import { edit as rescheduleEdit } from '@/routes/bookings/reschedule';
import type { BookingDetail } from '@/types';

type Props = {
    booking: BookingDetail & {
        reschedule_count: number;
        rescheduled_from: {
            date: string;
            start_time: string;
            end_time: string;
            field_name: string;
        } | null;
    };
    canCancel: boolean;
    cancelRule: { allowed: boolean; reason: string | null; refunds_dp: boolean };
    rescheduleRule: { allowed: boolean; reason: string | null };
};

function Baris({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex justify-between gap-4 py-2">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-right text-sm font-medium">{value}</span>
        </div>
    );
}

export default function BookingsShow({
    booking,
    canCancel,
    cancelRule,
    rescheduleRule,
}: Props) {
    const sudahCheckIn = booking.checked_in_at !== null;
    const [dialogBatal, setDialogBatal] = useState(false);
    const [alasan, setAlasan] = useState('');

    return (
        <>
            <Head title={`Booking ${booking.code}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Booking', href: index() },
                        { title: booking.code },
                    ]}
                    title={booking.code}
                    description={`${booking.field_name} — ${booking.branch_name}`}
                    action={
                        <Badge
                            className={warnaStatusBooking(booking.status)}
                        >
                            {formatStatusBooking(booking.status)}
                        </Badge>
                    }
                />

                <div className="grid max-w-4xl gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Jadwal</CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y">
                            <Baris
                                label="Tanggal"
                                value={formatTanggal(booking.booking_date)}
                            />
                            <Baris
                                label="Jam"
                                value={`${booking.start_time}–${booking.end_time}`}
                            />
                            <Baris
                                label="Durasi"
                                value={`${booking.duration_hours} jam`}
                            />
                            <Baris label="Lapangan" value={booking.field_name} />
                            <Baris
                                label="Sumber"
                                value={
                                    booking.source === 'walkin'
                                        ? 'Walk-in'
                                        : 'Online'
                                }
                            />
                            {booking.expired_at && booking.status === 'pending' && (
                                <Baris
                                    label="Batas bayar"
                                    value={formatWaktuWib(booking.expired_at)}
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Pelanggan</CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y">
                            <Baris label="Nama" value={booking.customer_name} />
                            <Baris
                                label="No. WhatsApp"
                                value={
                                    <span className="font-mono">
                                        {booking.customer_phone}
                                    </span>
                                }
                            />
                            <Baris
                                label="Harga member"
                                value={booking.is_member_price ? 'Ya' : 'Tidak'}
                            />
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Tagihan</CardTitle>
                            <CardDescription>
                                Harga dikunci saat booking dibuat — perubahan
                                aturan harga tidak mempengaruhi booking ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="divide-y">
                            <Baris
                                label={`Sewa lapangan (${formatRupiah(booking.price_per_hour)} × ${booking.duration_hours} jam)`}
                                value={formatRupiah(booking.subtotal_field)}
                            />
                            {booking.subtotal_addons > 0 && (
                                <Baris
                                    label="Add-on"
                                    value={formatRupiah(booking.subtotal_addons)}
                                />
                            )}
                            <Baris
                                label="Total"
                                value={
                                    <span className="text-base">
                                        {formatRupiah(booking.total)}
                                    </span>
                                }
                            />
                            <Baris
                                label="DP disepakati"
                                value={formatRupiah(booking.dp_amount)}
                            />
                            <Baris
                                label="Sudah dibayar"
                                value={formatRupiah(booking.paid_amount)}
                            />
                            <Baris
                                label="Sisa tagihan"
                                value={
                                    <span
                                        className={
                                            booking.outstanding > 0
                                                ? 'text-red-600'
                                                : 'text-green-600'
                                        }
                                    >
                                        {formatRupiah(booking.outstanding)}
                                    </span>
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Kedatangan</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between gap-4">
                            {sudahCheckIn ? (
                                <p className="flex items-center gap-2 text-sm text-green-700">
                                    <CheckCircle2 className="size-4" />
                                    Check-in pada{' '}
                                    {formatWaktuWib(booking.checked_in_at!)}
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {booking.outstanding > 0
                                        ? 'Sisa tagihan harus dilunasi sebelum check-in.'
                                        : 'Pelanggan belum check-in.'}
                                </p>
                            )}

                            {!sudahCheckIn && (
                                <Button
                                    onClick={() =>
                                        router.post(
                                            checkIn(booking.id).url,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    disabled={booking.outstanding > 0}
                                >
                                    Check-in
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {booking.rescheduled_from && (
                    <p className="mt-4 max-w-4xl text-sm text-muted-foreground">
                        Jadwal sebelumnya:{' '}
                        {formatTanggal(booking.rescheduled_from.date)},{' '}
                        {booking.rescheduled_from.start_time}–
                        {booking.rescheduled_from.end_time} di{' '}
                        {booking.rescheduled_from.field_name}. Sudah
                        di-reschedule {booking.reschedule_count} kali.
                    </p>
                )}

                <div className="mt-4 flex max-w-4xl flex-wrap gap-2">
                    <Button
                        variant="outline"
                        onClick={() => router.get(grid().url)}
                    >
                        Kembali ke Grid
                    </Button>

                    {rescheduleRule.allowed ? (
                        <Button variant="outline" asChild>
                            <Link href={rescheduleEdit(booking.id)}>
                                <CalendarClock className="size-4" />
                                Reschedule
                            </Link>
                        </Button>
                    ) : (
                        <span className="self-center text-sm text-muted-foreground">
                            {rescheduleRule.reason}
                        </span>
                    )}

                    {canCancel && cancelRule.allowed && (
                        <Button
                            variant="destructive"
                            onClick={() => setDialogBatal(true)}
                        >
                            Batalkan
                        </Button>
                    )}
                </div>

                {!canCancel && (
                    <p className="mt-2 text-sm text-muted-foreground">
                        Pembatalan hanya dapat dilakukan admin atau owner.
                    </p>
                )}
            </div>

            <AlertDialog open={dialogBatal} onOpenChange={setDialogBatal}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Batalkan booking {booking.code}?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {cancelRule.refunds_dp
                                ? `Pembatalan masih dalam batas kebijakan — DP ${formatRupiah(booking.dp_amount)} dikembalikan.`
                                : `Pembatalan sudah melewati batas kebijakan — DP ${formatRupiah(booking.dp_amount)} akan HANGUS.`}{' '}
                            Slot akan kembali tersedia untuk pelanggan lain.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="alasan">Alasan pembatalan</Label>
                        <Input
                            id="alasan"
                            value={alasan}
                            onChange={(e) => setAlasan(e.target.value)}
                            placeholder="Opsional, contoh: Hujan deras"
                        />
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Kembali</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                router.post(
                                    cancel(booking.id).url,
                                    { reason: alasan || null },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Batalkan Booking
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
