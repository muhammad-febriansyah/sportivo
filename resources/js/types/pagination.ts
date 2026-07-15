/** Bentuk JSON dari Laravel `->paginate()`. */
export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    path: string;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
};

/** Query string yang dibaca controller untuk list server-side. */
export type TableQuery = {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    page?: number;
};
