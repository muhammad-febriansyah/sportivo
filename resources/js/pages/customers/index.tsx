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
import { columns } from '@/pages/customers/columns';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/customers';
import type { CustomerRow, Paginator, TableQuery } from '@/types';

type CustomerQuery = TableQuery & { member?: string };

type Props = {
    customers: Paginator<CustomerRow>;
    query: CustomerQuery;
};

const SEMUA = 'semua';

export default function CustomersIndex({ customers, query }: Props) {
    const tombolTambah = (
        <Button asChild>
            <Link href={create()}>
                <Plus className="size-4" />
                Tambah Pelanggan
            </Link>
        </Button>
    );

    return (
        <>
            <Head title="Pelanggan" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Pelanggan' },
                    ]}
                    title="Pelanggan"
                    description="Data penyewa dan status membership."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            paginator={customers}
                            query={query}
                            only={['customers', 'query']}
                            searchPlaceholder="Cari nama, no. WhatsApp, atau email…"
                            filters={
                                <Select
                                    value={query.member ?? SEMUA}
                                    onValueChange={(v) =>
                                        router.get(
                                            index().url,
                                            {
                                                ...query,
                                                member:
                                                    v === SEMUA ? undefined : v,
                                                page: 1,
                                            },
                                            {
                                                only: ['customers', 'query'],
                                                preserveState: true,
                                                replace: true,
                                            },
                                        )
                                    }
                                >
                                    <SelectTrigger className="sm:w-52">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={SEMUA}>
                                            Semua pelanggan
                                        </SelectItem>
                                        <SelectItem value="member">
                                            Member aktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            }
                            emptyState={{
                                title: 'Belum ada pelanggan',
                                description:
                                    'Pelanggan juga bisa dibuat langsung saat input booking walk-in.',
                                action: tombolTambah,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
