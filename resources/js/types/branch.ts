export type OperatingHours = {
    weekday: { open: string; close: string };
    weekend: { open: string; close: string };
};

/** Baris cabang pada DataTable. */
export type BranchRow = {
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    is_active: boolean;
    users_count: number;
    province: { id: number; name: string } | null;
    city: { id: number; name: string } | null;
};

/** Bentuk cabang pada halaman edit. */
export type BranchFormData = {
    id: number;
    name: string;
    code: string;
    address: string;
    province_id: number | null;
    city_id: number | null;
    district_id: number | null;
    phone: string;
    operating_hours: OperatingHours;
    photo_url: string | null;
    is_active: boolean;
};
