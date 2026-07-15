import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    formatRupiah,
    formatStatusBooking,
    formatTanggal,
    warnaStatusBooking,
} from '@/lib/format';
import { dashboard } from '@/routes';
import { show as showBooking } from '@/routes/bookings';
import { edit, index } from '@/routes/customers';
import type { BookingStatus } from '@/types';

type RiwayatBooking = {
    id: number;
    code: string;
    branch_name: string;
    field_name: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    total: number;
    status: BookingStatus;
};

type Props = {
    customer: {
        id: number;
        name: string;
        phone: string;
        email: string | null;
        is_member: boolean;
        member_until: string | null;
        is_active_member: boolean;
        notes: string | null;
    };
    bookings: RiwayatBooking[];
    canUpdate: boolean;
};

export default function CustomersShow({ customer, bookings, canUpdate }: Props) {
    return (
        <>
            <Head title={customer.name} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Pelanggan', href: index() },
                        { title: customer.name },
                    ]}
                    title={customer.name}
                    description={customer.phone}
                    action={
                        canUpdate ? (
                            <Button asChild>
                                <Link href={edit(customer.id)}>
                                    <Pencil className="size-4" />
                                    Edit
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />

                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profil</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    No. WhatsApp
                                </p>
                                <p className="font-mono font-medium">
                                    {customer.phone}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Email
                                </p>
                                <p className="font-medium">
                                    {customer.email ?? '—'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Status Member
                                </p>
                                {!customer.is_member ? (
                                    <p className="font-medium">Bukan member</p>
                                ) : customer.is_active_member ? (
                                    <Badge className="bg-green-600 text-white">
                                        {customer.member_until
                                            ? `Aktif sampai ${formatTanggal(customer.member_until)}`
                                            : 'Aktif tanpa batas'}
                                    </Badge>
                                ) : (
                                    <Badge
                                        variant="outline"
                                        className="text-red-600"
                                    >
                                        Kedaluwarsa{' '}
                                        {customer.member_until &&
                                            formatTanggal(customer.member_until)}{' '}
                                        — memakai harga umum
                                    </Badge>
                                )}
                            </div>
                            {customer.notes && (
                                <div className="sm:col-span-2">
                                    <p className="text-sm text-muted-foreground">
                                        Catatan Internal
                                    </p>
                                    <p className="text-sm">{customer.notes}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat Booking</CardTitle>
                            <CardDescription>
                                50 booking terakhir yang dapat Anda akses.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kode</TableHead>
                                            <TableHead>Lapangan</TableHead>
                                            <TableHead>Jadwal</TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bookings.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="h-24 text-center text-muted-foreground"
                                                >
                                                    Belum ada booking.
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {bookings.map((b) => (
                                            <TableRow key={b.id}>
                                                <TableCell>
                                                    <Link
                                                        href={showBooking(b.id)}
                                                        className="font-mono text-primary hover:underline"
                                                    >
                                                        {b.code}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    <p>{b.field_name}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {b.branch_name}
                                                    </p>
                                                </TableCell>
                                                <TableCell>
                                                    <p>
                                                        {formatTanggal(
                                                            b.booking_date,
                                                        )}
                                                    </p>
                                                    <p className="font-mono text-xs text-muted-foreground">
                                                        {b.start_time}–
                                                        {b.end_time}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatRupiah(b.total)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={warnaStatusBooking(
                                                            b.status,
                                                        )}
                                                    >
                                                        {formatStatusBooking(
                                                            b.status,
                                                        )}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
