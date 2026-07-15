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
import { columns } from '@/pages/fields/columns';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/fields';
import type {
    FieldRow,
    Paginator,
    SelectOption,
    TableQuery,
} from '@/types';

type FieldQuery = TableQuery & {
    branch_id?: number | string;
    status?: string;
};

type Props = {
    fields: Paginator<FieldRow>;
    query: FieldQuery;
    branches: SelectOption<number>[];
    statuses: SelectOption<string>[];
};

const SEMUA = 'semua';

export default function FieldsIndex({
    fields,
    query,
    branches,
    statuses,
}: Props) {
    const tombolTambah = (
        <Button asChild>
            <Link href={create()}>
                <Plus className="size-4" />
                Tambah Lapangan
            </Link>
        </Button>
    );

    function filter(nama: 'branch_id' | 'status', nilai: string): void {
        router.get(
            index().url,
            { ...query, [nama]: nilai === SEMUA ? undefined : nilai, page: 1 },
            { only: ['fields', 'query'], preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Master Lapangan" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Lapangan' },
                    ]}
                    title="Master Lapangan"
                    description="Kelola lapangan, tipe rumput, dan status ketersediaannya."
                    action={tombolTambah}
                />

                <Card>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            paginator={fields}
                            query={query}
                            only={['fields', 'query']}
                            searchPlaceholder="Cari nama lapangan…"
                            filters={
                                <>
                                    {/* Admin hanya punya satu cabang, filternya tidak berguna. */}
                                    {branches.length > 1 && (
                                        <Select
                                            value={
                                                query.branch_id
                                                    ? String(query.branch_id)
                                                    : SEMUA
                                            }
                                            onValueChange={(v) =>
                                                filter('branch_id', v)
                                            }
                                        >
                                            <SelectTrigger className="sm:w-52">
                                                <SelectValue placeholder="Semua cabang" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={SEMUA}>
                                                    Semua cabang
                                                </SelectItem>
                                                {branches.map((b) => (
                                                    <SelectItem
                                                        key={b.value}
                                                        value={String(b.value)}
                                                    >
                                                        {b.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}

                                    <Select
                                        value={query.status ?? SEMUA}
                                        onValueChange={(v) =>
                                            filter('status', v)
                                        }
                                    >
                                        <SelectTrigger className="sm:w-44">
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
                                </>
                            }
                            emptyState={{
                                title: 'Belum ada lapangan',
                                description:
                                    'Tambahkan lapangan agar bisa diatur harganya dan muncul di grid booking.',
                                action: tombolTambah,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
