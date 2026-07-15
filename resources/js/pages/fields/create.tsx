import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { FieldForm } from '@/pages/fields/field-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/fields';
import type { FieldStatus, SelectOption, SurfaceType } from '@/types';

type Props = {
    branches: SelectOption<number>[];
    surfaceTypes: SelectOption<SurfaceType>[];
    statuses: SelectOption<FieldStatus>[];
    lockedBranchId: number | null;
};

export default function FieldsCreate({
    branches,
    surfaceTypes,
    statuses,
    lockedBranchId,
}: Props) {
    return (
        <>
            <Head title="Tambah Lapangan" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Lapangan', href: index() },
                        { title: 'Tambah Lapangan' },
                    ]}
                    title="Tambah Lapangan"
                    description="Tambahkan lapangan baru untuk cabang Anda."
                />

                <div className="max-w-3xl">
                    <FieldForm
                        branches={branches}
                        surfaceTypes={surfaceTypes}
                        statuses={statuses}
                        lockedBranchId={lockedBranchId}
                    />
                </div>
            </div>
        </>
    );
}
