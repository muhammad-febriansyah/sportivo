export type UserRole = 'owner' | 'admin' | 'kasir';

export type SelectOption<T = string> = {
    value: T;
    label: string;
};

/** Baris user pada DataTable manajemen user. */
export type UserRow = {
    id: number;
    branch_id: number | null;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    branch: { id: number; name: string } | null;
    roles: { id: number; name: UserRole }[];
};

/** Bentuk user pada halaman edit. */
export type UserFormData = {
    id: number;
    branch_id: number | null;
    name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    role: UserRole | null;
};
