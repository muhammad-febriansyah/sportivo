import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { DatePickerField } from '@/components/date-picker-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatRupiahRingkas } from '@/lib/format';
import { dashboard } from '@/routes';
import { create, grid as gridRoute, show } from '@/routes/bookings';
import type { AvailabilityGrid, GridSlot, SelectOption } from '@/types';

type Props = {
    grid: AvailabilityGrid;
    branch: { id: number; name: string };
    branches: SelectOption<number>[];
    date: string;
};

/**
 * Warna sel mengikuti docs/04-design-system.md bagian Booking Grid.
 */
const GAYA_SLOT: Record<string, string> = {
    available: 'bg-white hover:ring-2 hover:ring-primary cursor-pointer',
    pending: 'bg-slate-100 text-slate-700',
    dp: 'bg-orange-100 text-orange-900',
    paid: 'bg-green-100 text-green-900',
    blocked: 'bg-slate-200 text-slate-600',
    past: 'bg-slate-50 text-slate-400 opacity-60',
    no_price: 'bg-red-50 text-red-700',
};

export default function BookingGrid({ grid, branch, branches, date }: Props) {
    function pindah(tanggalBaru: string, cabangBaru?: number): void {
        router.get(
            gridRoute().url,
            { date: tanggalBaru, branch_id: cabangBaru ?? branch.id },
            { preserveState: true, replace: true },
        );
    }

    function geserHari(jumlah: number): void {
        const d = new Date(`${date}T00:00:00`);
        d.setDate(d.getDate() + jumlah);
        pindah(d.toISOString().slice(0, 10));
    }

    function isiSel(slot: GridSlot): string {
        if (slot.state === 'no_price') {
            return 'Belum diatur';
        }

        if (slot.state === 'blocked') {
            return slot.reason ?? 'Diblokir';
        }

        if (slot.customer_name) {
            return slot.customer_name;
        }

        return slot.price !== null ? formatRupiahRingkas(slot.price) : '—';
    }

    return (
        <>
            <Head title="Grid Booking" />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Grid Booking' },
                    ]}
                    title="Grid Booking"
                    description="Klik slot kosong untuk membuat booking baru."
                />

                <Card>
                    <CardContent className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            {branches.length > 1 && (
                                <div className="sm:w-56">
                                    <Select
                                        value={String(branch.id)}
                                        onValueChange={(v) =>
                                            pindah(date, Number(v))
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {branches.map((b) => (
                                                <SelectItem
                                                    key={b.value}
                                                    value={String(b.value)}
                                                >
                                                    {b.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="sm:w-56">
                                <DatePickerField
                                    id="tanggal_grid"
                                    label="Tanggal"
                                    value={date}
                                    onChange={(v) => v && pindah(v)}
                                />
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => geserHari(-1)}
                                >
                                    <ChevronLeft className="size-4" />
                                    Kemarin
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        pindah(
                                            new Date()
                                                .toISOString()
                                                .slice(0, 10),
                                        )
                                    }
                                >
                                    Hari Ini
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => geserHari(1)}
                                >
                                    Besok
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>

                        {grid.fields.length === 0 ? (
                            <p className="py-12 text-center text-muted-foreground">
                                Belum ada lapangan di cabang ini.
                            </p>
                        ) : (
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full border-collapse text-sm">
                                    <thead>
                                        <tr>
                                            <th className="sticky left-0 z-10 border-b border-r bg-background p-2 text-left font-medium">
                                                Jam
                                            </th>
                                            {grid.fields.map((f) => (
                                                <th
                                                    key={f.id}
                                                    className="min-w-32 border-b p-2 text-center font-medium"
                                                >
                                                    {f.name}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {grid.hours.map((jam) => (
                                            <tr key={jam}>
                                                <td className="sticky left-0 z-10 border-r bg-background p-2 font-mono text-xs">
                                                    {jam}
                                                </td>
                                                {grid.fields.map((f) => {
                                                    const slot =
                                                        grid.slots[f.id]?.[jam];

                                                    if (!slot) {
                                                        return (
                                                            <td
                                                                key={f.id}
                                                                className="border p-2"
                                                            />
                                                        );
                                                    }

                                                    const gaya =
                                                        GAYA_SLOT[slot.state] ??
                                                        '';
                                                    const isi = isiSel(slot);

                                                    // Slot terisi mengarah ke detail booking,
                                                    // slot kosong ke form create ter-prefill.
                                                    if (
                                                        slot.state ===
                                                            'available'
                                                    ) {
                                                        return (
                                                            <td
                                                                key={f.id}
                                                                className="border p-0"
                                                            >
                                                                <Link
                                                                    href={
                                                                        create({
                                                                            query: {
                                                                                field_id:
                                                                                    f.id,
                                                                                date,
                                                                                start_time:
                                                                                    jam,
                                                                            },
                                                                        }).url
                                                                    }
                                                                    className={`block h-full w-full p-2 text-center text-xs ${gaya}`}
                                                                >
                                                                    {isi}
                                                                </Link>
                                                            </td>
                                                        );
                                                    }

                                                    if (slot.booking_id) {
                                                        return (
                                                            <td
                                                                key={f.id}
                                                                className="border p-0"
                                                            >
                                                                <Link
                                                                    href={show(
                                                                        slot.booking_id,
                                                                    )}
                                                                    className={`block h-full w-full p-2 text-center text-xs ${gaya}`}
                                                                >
                                                                    {isi}
                                                                </Link>
                                                            </td>
                                                        );
                                                    }

                                                    return (
                                                        <td
                                                            key={f.id}
                                                            className={`border p-2 text-center text-xs ${gaya}`}
                                                        >
                                                            {isi}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-4 text-xs text-muted-foreground">
                            <Keterangan gaya={GAYA_SLOT.available} teks="Tersedia" />
                            <Keterangan gaya={GAYA_SLOT.dp} teks="DP" />
                            <Keterangan gaya={GAYA_SLOT.paid} teks="Lunas" />
                            <Keterangan gaya={GAYA_SLOT.pending} teks="Menunggu bayar" />
                            <Keterangan gaya={GAYA_SLOT.blocked} teks="Diblokir" />
                            <Keterangan gaya={GAYA_SLOT.no_price} teks="Harga belum diatur" />
                            <Keterangan gaya={GAYA_SLOT.past} teks="Lewat waktu" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Keterangan({ gaya, teks }: { gaya: string; teks: string }) {
    return (
        <span className="flex items-center gap-1.5">
            <span className={`size-3 rounded border ${gaya}`} />
            {teks}
        </span>
    );
}
