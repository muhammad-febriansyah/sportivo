import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { BranchForm } from '@/pages/branches/branch-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/branches';
import type { BranchFormData, SelectOption } from '@/types';

type Props = {
    branch: BranchFormData;
    provinces: SelectOption<number>[];
    cities: SelectOption<number>[];
    districts: SelectOption<number>[];
};

export default function BranchesEdit({
    branch,
    provinces,
    cities,
    districts,
}: Props) {
    return (
        <>
            <Head title={`Edit ${branch.name}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Cabang', href: index() },
                        { title: branch.name },
                    ]}
                    title="Edit Cabang"
                    description="Perbarui data cabang, wilayah, dan jam operasional."
                />

                <div className="max-w-3xl">
                    <BranchForm
                        provinces={provinces}
                        cities={cities}
                        districts={districts}
                        branch={branch}
                    />
                </div>
            </div>
        </>
    );
}
