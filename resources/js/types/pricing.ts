export type DayType =
    | 'weekday'
    | 'weekend'
    | 'monday'
    | 'tuesday'
    | 'wednesday'
    | 'thursday'
    | 'friday'
    | 'saturday'
    | 'sunday';

export type PricingRuleRow = {
    id: number;
    day_type: DayType;
    day_label: string;
    start_time: string;
    end_time: string;
    price: number;
    member_price: number | null;
};

/**
 * Preview matriks harga mingguan.
 * `cells[day_type][jam]` = harga, atau null bila belum diatur.
 */
export type PricingMatrix = {
    hours: string[];
    days: { day_type: DayType; label: string; is_weekend: boolean }[];
    cells: Record<string, Record<string, number | null>>;
    gaps: number;
};
