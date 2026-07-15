import { router } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown, Inbox } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginator, TableQuery } from '@/types';

type DataTableProps<TData> = {
    columns: ColumnDef<TData, unknown>[];
    paginator: Paginator<TData>;
    /** Nilai filter aktif, dikirim balik dari controller agar input tetap sinkron. */
    query?: TableQuery;
    /** Nama prop Inertia yang di-reload parsial. Kosongkan untuk reload penuh. */
    only?: string[];
    searchPlaceholder?: string;
    /** Filter tambahan di samping kolom search, contoh: Select cabang. */
    filters?: ReactNode;
    /** True saat memakai deferred props — menampilkan skeleton. */
    isLoading?: boolean;
    emptyState?: {
        title?: string;
        description?: string;
        action?: ReactNode;
    };
};

/**
 * Tabel list standar aplikasi. Pagination, sorting, dan search dijalankan
 * SERVER-SIDE lewat partial reload Inertia — jangan memuat semua baris ke klien.
 *
 * Lihat docs/04-design-system.md.
 */
export function DataTable<TData>({
    columns,
    paginator,
    query = {},
    only,
    searchPlaceholder = 'Cari…',
    filters,
    isLoading,
    emptyState,
}: DataTableProps<TData>) {
    const [search, setSearch] = useState(query.search ?? '');
    const isFirstRender = useRef(true);

    const table = useReactTable({
        data: paginator.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        pageCount: paginator.last_page,
    });

    function visit(params: TableQuery): void {
        router.get(
            paginator.path,
            { ...query, ...params },
            {
                only,
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    // Search di-debounce agar tiap ketikan tidak memicu request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            if (search === (query.search ?? '')) {
                return;
            }

            visit({ search: search || undefined, page: 1 });
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function handleSort(columnId: string): void {
        const arahBaru =
            query.sort === columnId && query.direction === 'asc'
                ? 'desc'
                : 'asc';

        visit({ sort: columnId, direction: arahBaru, page: 1 });
    }

    const jumlahKolom = columns.length;
    const kosong = paginator.data.length === 0;

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={searchPlaceholder}
                    className="sm:max-w-xs"
                />
                {filters}
            </div>

            <div className="overflow-x-auto rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const bisaDiurutkan =
                                        header.column.columnDef
                                            .enableSorting !== false;
                                    const aktif =
                                        query.sort === header.column.id;

                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder ? null : bisaDiurutkan ? (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="-ml-3 h-8"
                                                    onClick={() =>
                                                        handleSort(
                                                            header.column.id,
                                                        )
                                                    }
                                                >
                                                    {flexRender(
                                                        header.column.columnDef
                                                            .header,
                                                        header.getContext(),
                                                    )}
                                                    {!aktif && (
                                                        <ArrowUpDown className="size-3.5 opacity-50" />
                                                    )}
                                                    {aktif &&
                                                        query.direction ===
                                                            'asc' && (
                                                            <ArrowUp className="size-3.5" />
                                                        )}
                                                    {aktif &&
                                                        query.direction ===
                                                            'desc' && (
                                                            <ArrowDown className="size-3.5" />
                                                        )}
                                                </Button>
                                            ) : (
                                                flexRender(
                                                    header.column.columnDef
                                                        .header,
                                                    header.getContext(),
                                                )
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>

                    <TableBody>
                        {isLoading &&
                            Array.from({ length: 5 }).map((_, baris) => (
                                <TableRow key={`skeleton-${baris}`}>
                                    {Array.from({ length: jumlahKolom }).map(
                                        (__, kolom) => (
                                            <TableCell key={`sel-${kolom}`}>
                                                <Skeleton className="h-5 w-full animate-pulse" />
                                            </TableCell>
                                        ),
                                    )}
                                </TableRow>
                            ))}

                        {!isLoading && kosong && (
                            <TableRow>
                                <TableCell
                                    colSpan={jumlahKolom}
                                    className="h-48 text-center"
                                >
                                    <div className="flex flex-col items-center gap-2">
                                        <Inbox className="size-8 text-muted-foreground" />
                                        <p className="font-medium">
                                            {emptyState?.title ??
                                                'Belum ada data'}
                                        </p>
                                        {emptyState?.description && (
                                            <p className="text-sm text-muted-foreground">
                                                {emptyState.description}
                                            </p>
                                        )}
                                        {emptyState?.action && (
                                            <div className="mt-2">
                                                {emptyState.action}
                                            </div>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        )}

                        {!isLoading &&
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))}
                    </TableBody>
                </Table>
            </div>

            {!kosong && (
                <div className="flex flex-col items-center justify-between gap-2 sm:flex-row">
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {paginator.from ?? 0}–{paginator.to ?? 0}{' '}
                        dari {paginator.total} data
                    </p>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={paginator.current_page <= 1}
                            onClick={() =>
                                visit({ page: paginator.current_page - 1 })
                            }
                        >
                            Sebelumnya
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            Hal {paginator.current_page} dari{' '}
                            {paginator.last_page}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={
                                paginator.current_page >= paginator.last_page
                            }
                            onClick={() =>
                                visit({ page: paginator.current_page + 1 })
                            }
                        >
                            Berikutnya
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
