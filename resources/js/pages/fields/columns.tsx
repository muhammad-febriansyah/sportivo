import { Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatStatusLapangan, formatTipeRumput } from '@/lib/format';
import { destroy, edit } from '@/routes/fields';
import type { FieldRow, FieldStatus } from '@/types';

function BadgeStatus({ status }: { status: FieldStatus }) {
    const teks = formatStatusLapangan(status);

    if (status === 'active') {
        return <Badge className="bg-green-600 text-white">{teks}</Badge>;
    }

    if (status === 'maintenance') {
        return <Badge className="bg-orange-500 text-white">{teks}</Badge>;
    }

    return <Badge variant="destructive">{teks}</Badge>;
}

function AksiLapangan({ field }: { field: FieldRow }) {
    return (
        <AlertDialog>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon-sm">
                        <MoreHorizontal className="size-4" />
                        <span className="sr-only">Aksi untuk {field.name}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem asChild>
                        <Link href={edit(field.id)}>Edit</Link>
                    </DropdownMenuItem>
                    <AlertDialogTrigger asChild>
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={(e) => e.preventDefault()}
                        >
                            Hapus
                        </DropdownMenuItem>
                    </AlertDialogTrigger>
                </DropdownMenuContent>
            </DropdownMenu>

            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus lapangan ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Lapangan <strong>{field.name}</strong> akan dihapus dari
                        grid booking. Untuk menutup sementara tanpa menghapus,
                        ubah statusnya menjadi Maintenance.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={() =>
                            router.delete(destroy(field.id).url, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export const columns: ColumnDef<FieldRow, unknown>[] = [
    {
        id: 'name',
        accessorKey: 'name',
        header: 'Nama Lapangan',
        cell: ({ row }) => (
            <span className="font-medium">{row.original.name}</span>
        ),
    },
    {
        id: 'branch',
        header: 'Cabang',
        enableSorting: false,
        cell: ({ row }) => row.original.branch?.name ?? '—',
    },
    {
        id: 'surface_type',
        accessorKey: 'surface_type',
        header: 'Tipe Rumput',
        cell: ({ row }) => formatTipeRumput(row.original.surface_type),
    },
    {
        id: 'size',
        header: 'Ukuran',
        enableSorting: false,
        cell: ({ row }) => row.original.size ?? '—',
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => <BadgeStatus status={row.original.status} />,
    },
    {
        id: 'actions',
        header: '',
        enableSorting: false,
        cell: ({ row }) => (
            <div className="text-right">
                <AksiLapangan field={row.original} />
            </div>
        ),
    },
];
