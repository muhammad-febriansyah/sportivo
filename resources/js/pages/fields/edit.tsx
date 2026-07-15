import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { FieldForm } from '@/pages/fields/field-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/fields';
import type {
    FieldFormData,
    FieldStatus,
    SelectOption,
    SurfaceType,
} from '@/types';

type Props = {
    field: FieldFormData;
    branches: SelectOption<number>[];
    surfaceTypes: SelectOption<SurfaceType>[];
    statuses: SelectOption<FieldStatus>[];
    lockedBranchId: number | null;
};

export default function FieldsEdit({
    field,
    branches,
    surfaceTypes,
    statuses,
    lockedBranchId,
}: Props) {
    return (
        <>
            <Head title={`Edit ${field.name}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Lapangan', href: index() },
                        { title: field.name },
                    ]}
                    title="Edit Lapangan"
                    description="Perbarui data lapangan dan status ketersediaannya."
                />

                <div className="max-w-3xl">
                    <FieldForm
                        branches={branches}
                        surfaceTypes={surfaceTypes}
                        statuses={statuses}
                        lockedBranchId={lockedBranchId}
                        field={field}
                    />
                </div>
            </div>
        </>
    );
}
