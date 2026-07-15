import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { columns } from '@/pages/users/columns';
import { dashboard } from '@/routes';
import { create } from '@/routes/users';
import type { Paginator, TableQuery, UserRow } from '@/types';

type Props = {
    users: Paginator<UserRow>;
    query: TableQuery;
};

export default function UsersIndex({ users, query }: Props) {
    const tombolTambah = (
        <Button asChild>
            <Link href={create()}>
                <Plus className="size-4" />
                Tambah User
            </Link>
        </Button>
    );

    return (
        <>
            <Head title="Manajemen User" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'User' },
                    ]}
                    title="Manajemen User"
                    description="Kelola akun owner, admin cabang, dan kasir."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            paginator={users}
                            query={query}
                            only={['users', 'query']}
                            searchPlaceholder="Cari nama, email, atau no. WhatsApp…"
                            emptyState={{
                                title: 'Belum ada user',
                                description:
                                    'Tambahkan admin cabang atau kasir untuk mulai mengelola venue.',
                                action: tombolTambah,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
