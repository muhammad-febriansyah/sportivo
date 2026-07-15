import type { ReactNode } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import type { BreadcrumbItem } from '@/types';

type PageHeaderProps = {
    breadcrumbs: BreadcrumbItem[];
    title: string;
    description: string;
    /** Tombol aksi utama di kanan atas, contoh: tombol "Tambah". */
    action?: ReactNode;
};

/**
 * Anatomi wajib setiap page: breadcrumb → judul → deskripsi.
 * Lihat docs/04-design-system.md.
 */
export function PageHeader({
    breadcrumbs,
    title,
    description,
    action,
}: PageHeaderProps) {
    return (
        <div className="mb-6 space-y-4">
            <Breadcrumbs breadcrumbs={breadcrumbs} />

            <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>

                {action && <div className="shrink-0">{action}</div>}
            </div>
        </div>
    );
}
