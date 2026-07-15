import { SidebarTrigger } from '@/components/ui/sidebar';

/**
 * Breadcrumb TIDAK dirender di sini — tempatnya di dalam <PageHeader> pada tiap
 * page, sesuai anatomi halaman di docs/04-design-system.md.
 */
export function AppSidebarHeader() {
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <SidebarTrigger className="-ml-1" />
        </header>
    );
}
