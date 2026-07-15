import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { UserForm } from '@/pages/users/user-form';
import { dashboard } from '@/routes';
import { index } from '@/routes/users';
import type { SelectOption, UserFormData, UserRole } from '@/types';

type Props = {
    user: UserFormData;
    branches: SelectOption<number>[];
    roles: SelectOption<UserRole>[];
    canDeactivate: boolean;
};

export default function UsersEdit({
    user,
    branches,
    roles,
    canDeactivate,
}: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'User', href: index() },
                        { title: user.name },
                    ]}
                    title="Edit User"
                    description="Perbarui data akun, role, dan status aktif."
                />

                <div className="max-w-2xl">
                    <UserForm
                        branches={branches}
                        roles={roles}
                        user={user}
                        canDeactivate={canDeactivate}
                    />
                </div>
            </div>
        </>
    );
}
