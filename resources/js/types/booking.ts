export type BookingStatus =
    | 'pending'
    | 'confirmed_dp'
    | 'paid'
    | 'completed'
    | 'cancelled'
    | 'no_show';

export type BookingSource = 'online' | 'walkin';

/** Keadaan satu sel di grid ketersediaan. */
export type SlotState =
    | 'available'
    | 'pending'
    | 'dp'
    | 'paid'
    | 'blocked'
    | 'past'
    | 'no_price';

export type GridSlot = {
    state: SlotState;
    price: number | null;
    reason?: string | null;
    booking_id?: number | null;
    booking_code?: string | null;
    customer_name?: string | null;
};

export type AvailabilityGrid = {
    hours: string[];
    fields: { id: number; name: string; status: string }[];
    /** slots[field_id][jam] */
    slots: Record<number, Record<string, GridSlot>>;
};

export type BookingRow = {
    id: number;
    code: string;
    field_name: string;
    customer_name: string;
    customer_phone: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    total: number;
    paid_amount: number;
    status: BookingStatus;
    source: BookingSource;
};

export type BookingDetail = {
    id: number;
    code: string;
    branch_name: string;
    field_name: string;
    customer_name: string;
    customer_phone: string;
    customer_id: number;
    booking_date: string;
    start_time: string;
    end_time: string;
    duration_hours: number;
    price_per_hour: number;
    is_member_price: boolean;
    subtotal_field: number;
    subtotal_addons: number;
    total: number;
    dp_amount: number;
    paid_amount: number;
    outstanding: number;
    status: BookingStatus;
    source: BookingSource;
    checked_in_at: string | null;
    expired_at: string | null;
};

export type CustomerRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_member: boolean;
    member_until: string | null;
    bookings_count: number;
};

export type CustomerSearchResult = {
    id: number;
    name: string;
    phone: string;
    is_member: boolean;
};

export type CustomerFormData = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_member: boolean;
    member_until: string | null;
    notes: string | null;
};
