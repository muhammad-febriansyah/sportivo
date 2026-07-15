export type SurfaceType = 'sintetis' | 'vinyl' | 'interlock';
export type FieldStatus = 'active' | 'maintenance' | 'inactive';

/** Baris lapangan pada DataTable. */
export type FieldRow = {
    id: number;
    branch_id: number;
    name: string;
    surface_type: SurfaceType;
    size: string | null;
    status: FieldStatus;
    branch: { id: number; name: string } | null;
};

/** Bentuk lapangan pada halaman edit. */
export type FieldFormData = {
    id: number;
    branch_id: number;
    name: string;
    surface_type: SurfaceType;
    size: string | null;
    description: string | null;
    status: FieldStatus;
    photo_url: string | null;
};
