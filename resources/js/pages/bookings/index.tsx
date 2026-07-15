import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { columns } from '@/pages/bookings/columns';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/bookings';
import type {
    BookingRow,
    Paginator,
    SelectOption,
    TableQuery,
} from '@/types';

type BookingQuery = TableQuery & { status?: string };

type Props = {
    bookings: Paginator<BookingRow>;
    query: BookingQuery;
    statuses: SelectOption<string>[];
};

const SEMUA = 'semua';

export default function BookingsIndex({ bookings, query, statuses }: Props) {
    const tombolTambah = (
        <Button asChild>
            <Link href={create()}>
                <Plus className="size-4" />
                Booking Baru
            </Link>
        </Button>
    );

    return (
        <>
            <Head title="Booking" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Booking' },
                    ]}
                    title="Booking"
                    description="Riwayat dan status seluruh booking."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            paginator={bookings}
                            query={query}
                            only={['bookings', 'query']}
                            searchPlaceholder="Cari kode, nama, atau no. WhatsApp…"
                            filters={
                                <Select
                                    value={query.status ?? SEMUA}
                                    onValueChange={(v) =>
                                        router.get(
                                            index().url,
                                            {
                                                ...query,
                                                status:
                                                    v === SEMUA ? undefined : v,
                                                page: 1,
                                            },
                                            {
                                                only: ['bookings', 'query'],
                                                preserveState: true,
                                                replace: true,
                                            },
                                        )
                                    }
                                >
                                    <SelectTrigger className="sm:w-56">
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={SEMUA}>
                                            Semua status
                                        </SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem
                                                key={s.value}
                                                value={s.value}
                                            >
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            }
                            emptyState={{
                                title: 'Belum ada booking',
                                description:
                                    'Buat booking pertama lewat grid atau tombol di atas.',
                                action: tombolTambah,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
