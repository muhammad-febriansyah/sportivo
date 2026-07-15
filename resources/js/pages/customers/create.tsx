import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { CustomerForm } from '@/pages/customers/customer-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/customers';

type Props = {
    canManageMembership: boolean;
};

export default function CustomersCreate({ canManageMembership }: Props) {
    return (
        <>
            <Head title="Tambah Pelanggan" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Pelanggan', href: index() },
                        { title: 'Tambah Pelanggan' },
                    ]}
                    title="Tambah Pelanggan"
                    description="Daftarkan penyewa baru."
                />

                <div className="max-w-2xl">
                    <CustomerForm canManageMembership={canManageMembership} />
                </div>
            </div>
        </>
    );
}
