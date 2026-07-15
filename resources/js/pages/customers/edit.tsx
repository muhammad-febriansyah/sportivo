import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { CustomerForm } from '@/pages/customers/customer-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/customers';
import type { CustomerFormData } from '@/types';

type Props = {
    customer: CustomerFormData;
    canManageMembership: boolean;
};

export default function CustomersEdit({ customer, canManageMembership }: Props) {
    return (
        <>
            <Head title={`Edit ${customer.name}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Pelanggan', href: index() },
                        { title: customer.name },
                    ]}
                    title="Edit Pelanggan"
                    description="Perbarui data pelanggan dan status membership."
                />

                <div className="max-w-2xl">
                    <CustomerForm
                        customer={customer}
                        canManageMembership={canManageMembership}
                    />
                </div>
            </div>
        </>
    );
}
