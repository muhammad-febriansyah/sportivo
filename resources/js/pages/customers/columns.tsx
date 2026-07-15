import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatTanggal } from '@/lib/format';
import { show } from '@/routes/customers';
import type { CustomerRow } from '@/types';

/**
 * Member kedaluwarsa ditandai berbeda: statusnya masih "member" di database,
 * tapi harganya sudah kembali ke harga umum.
 */
function BadgeMember({ customer }: { customer: CustomerRow }) {
    if (!customer.is_member) {
        return <span className="text-muted-foreground">—</span>;
    }

    const kedaluwarsa =
        customer.member_until !== null &&
        new Date(`${customer.member_until}T23:59:59`) < new Date();

    if (kedaluwarsa) {
        return (
            <Badge variant="outline" className="text-red-600">
                Kedaluwarsa {formatTanggal(customer.member_until!)}
            </Badge>
        );
    }

    return (
        <Badge className="bg-green-600 text-white">
            {customer.member_until
                ? `Sampai ${formatTanggal(customer.member_until)}`
                : 'Member'}
        </Badge>
    );
}

export const columns: ColumnDef<CustomerRow, unknown>[] = [
    {
        id: 'name',
        accessorKey: 'name',
        header: 'Nama',
        cell: ({ row }) => (
            <Link
                href={show(row.original.id)}
                className="font-medium text-primary hover:underline"
            >
                {row.original.name}
            </Link>
        ),
    },
    {
        id: 'phone',
        accessorKey: 'phone',
        header: 'No. WhatsApp',
        cell: ({ row }) => (
            <span className="font-mono">{row.original.phone}</span>
        ),
    },
    {
        id: 'bookings_count',
        header: 'Total Booking',
        enableSorting: false,
        cell: ({ row }) => row.original.bookings_count,
    },
    {
        id: 'is_member',
        accessorKey: 'is_member',
        header: 'Member',
        cell: ({ row }) => <BadgeMember customer={row.original} />,
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
