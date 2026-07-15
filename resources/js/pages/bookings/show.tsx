import { Head, router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatRupiah,
    formatStatusBooking,
    formatTanggal,
    formatWaktuWib,
    warnaStatusBooking,
} from '@/lib/format';
import { dashboard } from '@/routes';
import { checkIn, grid, index } from '@/routes/bookings';
import type { BookingDetail } from '@/types';

type Props = {
    booking: BookingDetail;
    canCancel: boolean;
};

function Baris({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex justify-between gap-4 py-2">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-right text-sm font-medium">{value}</span>
        </div>
    );
}

export default function BookingsShow({ booking, canCancel }: Props) {
    const sudahCheckIn = booking.checked_in_at !== null;

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

                {!canCancel && (
                    <p className="mt-4 text-sm text-muted-foreground">
                        Pembatalan hanya dapat dilakukan admin atau owner.
                    </p>
                )}
            </div>

            <div className="px-4 pb-4">
                <Button variant="outline" onClick={() => router.get(grid().url)}>
                    Kembali ke Grid
                </Button>
            </div>
        </>
    );
}
