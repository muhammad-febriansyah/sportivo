import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { BranchForm } from '@/pages/branches/branch-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/branches';
import type { SelectOption } from '@/types';

type Props = {
    provinces: SelectOption<number>[];
};

export default function BranchesCreate({ provinces }: Props) {
    return (
        <>
            <Head title="Tambah Cabang" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Cabang', href: index() },
                        { title: 'Tambah Cabang' },
                    ]}
                    title="Tambah Cabang"
                    description="Daftarkan cabang venue baru beserta jam operasionalnya."
                />

                <div className="max-w-3xl">
                    <BranchForm provinces={provinces} />
                </div>
            </div>
        </>
    );
}
