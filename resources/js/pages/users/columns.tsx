import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatRole } from '@/lib/format';
import { edit } from '@/routes/users';
import type { UserRow } from '@/types';

export const columns: ColumnDef<UserRow, unknown>[] = [
    {
        id: 'name',
        accessorKey: 'name',
        header: 'Nama',
        cell: ({ row }) => (
            <span className="font-medium">{row.original.name}</span>
        ),
    },
    {
        id: 'email',
        accessorKey: 'email',
        header: 'Email',
    },
    {
        id: 'phone',
        accessorKey: 'phone',
        header: 'No. WhatsApp',
        enableSorting: false,
        cell: ({ row }) => row.original.phone ?? '—',
    },
    {
        id: 'branch',
        header: 'Cabang',
        enableSorting: false,
        cell: ({ row }) => row.original.branch?.name ?? 'Semua cabang',
    },
    {
        id: 'role',
        header: 'Role',
        enableSorting: false,
        cell: ({ row }) => {
            const role = row.original.roles[0]?.name;

            return role ? (
                <Badge variant="secondary">{formatRole(role)}</Badge>
            ) : (
                '—'
            );
        },
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
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon-sm">
                            <MoreHorizontal className="size-4" />
                            <span className="sr-only">
                                Aksi untuk {row.original.name}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={edit(row.original.id)}>Edit</Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        ),
    },
];
