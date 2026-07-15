import { Link, usePage } from '@inertiajs/react';
import {
    Ban,
    BookOpen,
    Building2,
    CalendarDays,
    ClipboardList,
    Contact,
    FolderGit2,
    Goal,
    LayoutGrid,
    Package,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as addonsIndex } from '@/routes/addons';
import { index as blockedSlotsIndex } from '@/routes/blocked-slots';
import { grid as bookingsGrid, index as bookingsIndex } from '@/routes/bookings';
import { index as branchesIndex } from '@/routes/branches';
import { index as customersIndex } from '@/routes/customers';
import { index as fieldsIndex } from '@/routes/fields';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

// Seluruh role internal — pembatasannya per cabang, bukan per role.
const navUmum: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Grid Booking',
        href: bookingsGrid(),
        icon: CalendarDays,
    },
    {
        title: 'Booking',
        href: bookingsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Pelanggan',
        href: customersIndex(),
        icon: Contact,
    },
];

// Owner dan admin cabang — lihat FieldPolicy dan BlockedSlotPolicy.
const navMaster: NavItem[] = [
    {
        title: 'Lapangan',
        href: fieldsIndex(),
        icon: Goal,
    },
    {
        title: 'Add-on',
        href: addonsIndex(),
        icon: Package,
    },
    {
        title: 'Blocking Slot',
        href: blockedSlotsIndex(),
        icon: Ban,
    },
];

// Hanya owner — lihat UserPolicy dan BranchPolicy.
const navOwner: NavItem[] = [
    {
        title: 'Cabang',
        href: branchesIndex(),
        icon: Building2,
    },
    {
        title: 'User',
        href: usersIndex(),
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isOwner = auth.roles.includes('owner');
    const isAdmin = auth.roles.includes('admin');

    // Menyembunyikan menu hanya kosmetik — penjagaan sebenarnya ada di Policy.
    const navItems = [
        ...navUmum,
        ...(isOwner || isAdmin ? navMaster : []),
        ...(isOwner ? navOwner : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
