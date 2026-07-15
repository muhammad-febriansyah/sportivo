import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRupiah,
    formatStatusBooking,
    formatTanggal,
    warnaStatusBooking,
} from '@/lib/format';
import { show } from '@/routes/bookings';
import type { BookingRow } from '@/types';

export const columns: ColumnDef<BookingRow, unknown>[] = [
    {
        id: 'code',
        accessorKey: 'code',
        header: 'Kode',
        enableSorting: false,
        cell: ({ row }) => (
            <Link
                href={show(row.original.id)}
                className="font-mono font-medium text-primary hover:underline"
            >
                {row.original.code}
            </Link>
        ),
    },
    {
        id: 'customer',
        header: 'Pelanggan',
        enableSorting: false,
        cell: ({ row }) => (
            <div>
                <p className="font-medium">{row.original.customer_name}</p>
                <p className="font-mono text-xs text-muted-foreground">
                    {row.original.customer_phone}
                </p>
            </div>
        ),
    },
    {
        id: 'field_name',
        header: 'Lapangan',
        enableSorting: false,
        cell: ({ row }) => row.original.field_name,
    },
    {
        id: 'jadwal',
        header: 'Jadwal',
        enableSorting: false,
        cell: ({ row }) => (
            <div>
                <p>{formatTanggal(row.original.booking_date)}</p>
                <p className="font-mono text-xs text-muted-foreground">
                    {row.original.start_time}–{row.original.end_time}
                </p>
            </div>
        ),
    },
    {
        id: 'total',
        header: 'Total',
        enableSorting: false,
        cell: ({ row }) => (
            <div className="text-right">
                <p className="font-medium">{formatRupiah(row.original.total)}</p>
                {row.original.paid_amount < row.original.total && (
                    <p className="text-xs text-red-600">
                        Sisa{' '}
                        {formatRupiah(
                            row.original.total - row.original.paid_amount,
                        )}
                    </p>
                )}
            </div>
        ),
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        enableSorting: false,
        cell: ({ row }) => (
            <Badge className={warnaStatusBooking(row.original.status)}>
                {formatStatusBooking(row.original.status)}
            </Badge>
        ),
    },
    {
        id: 'actions',
        header: '',
        enableSorting: false,
        cell: ({ row }) => (
            <div className="text-right">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={show(row.original.id)}>Detail</Link>
                </Button>
            </div>
        ),
    },
];
