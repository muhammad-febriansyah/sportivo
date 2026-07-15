import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { UserForm } from '@/pages/users/user-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/users';
import type { SelectOption, UserRole } from '@/types';

type Props = {
    branches: SelectOption<number>[];
    roles: SelectOption<UserRole>[];
};

export default function UsersCreate({ branches, roles }: Props) {
    return (
        <>
            <Head title="Tambah User" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'User', href: index() },
                        { title: 'Tambah User' },
                    ]}
                    title="Tambah User"
                    description="Buat akun owner, admin cabang, atau kasir baru."
                />

                <div className="max-w-2xl">
                    <UserForm branches={branches} roles={roles} />
                </div>
            </div>
        </>
    );
}
