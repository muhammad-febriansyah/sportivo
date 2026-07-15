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
import { destroy, edit } from '@/routes/branches';
import type { BranchRow } from '@/types';

function AksiCabang({ branch }: { branch: BranchRow }) {
    return (
        <AlertDialog>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon-sm">
                        <MoreHorizontal className="size-4" />
                        <span className="sr-only">Aksi untuk {branch.name}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem asChild>
                        <Link href={edit(branch.id)}>Edit</Link>
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
                    <AlertDialogTitle>Hapus cabang ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Cabang <strong>{branch.name}</strong> akan dihapus.
                        Lapangan, harga, dan riwayat booking yang terkait ikut
                        tidak dapat diakses. Tindakan ini tidak bisa dibatalkan
                        dari halaman ini.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={() =>
                            router.delete(destroy(branch.id).url, {
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

export const columns: ColumnDef<BranchRow, unknown>[] = [
    {
        id: 'name',
        accessorKey: 'name',
        header: 'Nama Cabang',
        cell: ({ row }) => (
            <span className="font-medium">{row.original.name}</span>
        ),
    },
    {
        id: 'code',
        accessorKey: 'code',
        header: 'Kode',
        cell: ({ row }) => (
            <Badge variant="outline" className="font-mono">
                {row.original.code}
            </Badge>
        ),
    },
    {
        id: 'wilayah',
        header: 'Wilayah',
        enableSorting: false,
        cell: ({ row }) => {
            const { city, province } = row.original;

            if (!city && !province) {
                return '—';
            }

            return [city?.name, province?.name].filter(Boolean).join(', ');
        },
    },
    {
        id: 'phone',
        header: 'Telepon',
        enableSorting: false,
        cell: ({ row }) => row.original.phone,
    },
    {
        id: 'users_count',
        header: 'User',
        enableSorting: false,
        cell: ({ row }) => row.original.users_count,
    },
    {
        id: 'is_active',
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            row.original.is_active ? (
                <Badge className="bg-green-600 text-white">Aktif</Badge>
            ) : (
                <Badge variant="destructive">Nonaktif</Badge>
            ),
    },
    {
        id: 'actions',
        header: '',
        enableSorting: false,
        cell: ({ row }) => (
            <div className="text-right">
                <AksiCabang branch={row.original} />
            </div>
        ),
    },
];
