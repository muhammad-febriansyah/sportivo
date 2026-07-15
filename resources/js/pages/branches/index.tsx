import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { columns } from '@/pages/branches/columns';
import { dashboard } from '@/routes';
import { create } from '@/routes/branches';
import type { BranchRow, Paginator, TableQuery } from '@/types';

type Props = {
    branches: Paginator<BranchRow>;
    query: TableQuery;
};

export default function BranchesIndex({ branches, query }: Props) {
    const tombolTambah = (
        <Button asChild>
            <Link href={create()}>
                <Plus className="size-4" />
                Tambah Cabang
            </Link>
        </Button>
    );

    return (
        <>
            <Head title="Master Cabang" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Cabang' },
                    ]}
                    title="Master Cabang"
                    description="Kelola cabang venue, jam operasional, dan statusnya."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            paginator={branches}
                            query={query}
                            only={['branches', 'query']}
                            searchPlaceholder="Cari nama, kode, atau telepon…"
                            emptyState={{
                                title: 'Belum ada cabang',
                                description:
                                    'Tambahkan cabang pertama untuk mulai mengelola lapangan dan booking.',
                                action: tombolTambah,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
