import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah, formatRupiahRingkas } from '@/lib/format';
import { PricingRuleDialog } from '@/pages/pricing/pricing-rule-dialog';
import { dashboard } from '@/routes';
import { index as fieldsIndex } from '@/routes/fields';
import { destroy } from '@/routes/pricing';
import type {
    DayType,
    PricingMatrix,
    PricingRuleRow,
    SelectOption,
} from '@/types';

type Props = {
    field: { id: number; name: string; branch_name: string };
    rules: PricingRuleRow[];
    matrix: PricingMatrix;
    dayTypes: SelectOption<DayType>[];
};

export default function PricingIndex({
    field,
    rules,
    matrix,
    dayTypes,
}: Props) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const [ruleDiedit, setRuleDiedit] = useState<PricingRuleRow | undefined>();
    const [ruleDihapus, setRuleDihapus] = useState<PricingRuleRow | undefined>();

    function bukaTambah(): void {
        setRuleDiedit(undefined);
        setDialogTerbuka(true);
    }

    function bukaEdit(rule: PricingRuleRow): void {
        setRuleDiedit(rule);
        setDialogTerbuka(true);
    }

    return (
        <>
            <Head title={`Harga ${field.name}`} />

            <div className="p-4">
                <PageHeader
                    breadcrumbs={[
                        { title: 'Dashboard', href: dashboard() },
                        { title: 'Lapangan', href: fieldsIndex() },
                        { title: field.name },
                        { title: 'Harga' },
                    ]}
                    title={`Harga — ${field.name}`}
                    description={`Atur harga per jam untuk lapangan di ${field.branch_name}.`}
                    action={
                        <Button onClick={bukaTambah}>
                            <Plus className="size-4" />
                            Tambah Aturan
                        </Button>
                    }
                />

                {matrix.gaps > 0 && (
                    <div className="mb-4 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 p-4">
                        <AlertTriangle className="mt-0.5 size-5 shrink-0 text-red-600" />
                        <div className="text-sm">
                            <p className="font-medium text-red-900">
                                {matrix.gaps} slot belum punya harga
                            </p>
                            <p className="text-red-800">
                                Slot bertanda merah di preview tidak bisa
                                dibooking sampai harganya diatur.
                            </p>
                        </div>
                    </div>
                )}

                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Aturan Harga</CardTitle>
                            <CardDescription>
                                Aturan hari spesifik menang atas
                                weekday/weekend.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tipe Hari</TableHead>
                                            <TableHead>Jam</TableHead>
                                            <TableHead className="text-right">
                                                Harga
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Harga Member
                                            </TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rules.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="h-32 text-center"
                                                >
                                                    <p className="font-medium">
                                                        Belum ada aturan harga
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        Lapangan tidak bisa
                                                        dibooking sampai
                                                        harganya diatur.
                                                    </p>
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {rules.map((rule) => (
                                            <TableRow key={rule.id}>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {rule.day_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="font-mono">
                                                    {rule.start_time}–
                                                    {rule.end_time}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatRupiah(rule.price)}
                                                </TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    {rule.member_price === null
                                                        ? 'Ikut harga umum'
                                                        : formatRupiah(
                                                              rule.member_price,
                                                          )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        onClick={() =>
                                                            bukaEdit(rule)
                                                        }
                                                    >
                                                        <Pencil className="size-4" />
                                                        <span className="sr-only">
                                                            Edit aturan{' '}
                                                            {rule.day_label}
                                                        </span>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        onClick={() =>
                                                            setRuleDihapus(rule)
                                                        }
                                                    >
                                                        <Trash2 className="size-4 text-red-600" />
                                                        <span className="sr-only">
                                                            Hapus aturan{' '}
                                                            {rule.day_label}
                                                        </span>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Preview Harga Mingguan</CardTitle>
                            <CardDescription>
                                Hasil akhir resolusi harga per jam. Sel merah
                                berarti harga belum diatur.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="sticky left-0 bg-background">
                                                Jam
                                            </TableHead>
                                            {matrix.days.map((d) => (
                                                <TableHead
                                                    key={d.day_type}
                                                    className="text-center"
                                                >
                                                    {d.label}
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {matrix.hours.map((jam) => (
                                            <TableRow key={jam}>
                                                <TableCell className="sticky left-0 bg-background font-mono text-xs">
                                                    {jam}
                                                </TableCell>
                                                {matrix.days.map((d) => {
                                                    const harga =
                                                        matrix.cells[
                                                            d.day_type
                                                        ]?.[jam] ?? null;

                                                    return (
                                                        <TableCell
                                                            key={d.day_type}
                                                            className={
                                                                harga === null
                                                                    ? 'bg-red-50 text-center text-xs text-red-700'
                                                                    : 'text-center text-xs'
                                                            }
                                                        >
                                                            {harga === null
                                                                ? 'Belum diatur'
                                                                : formatRupiahRingkas(
                                                                      harga,
                                                                  )}
                                                        </TableCell>
                                                    );
                                                })}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <PricingRuleDialog
                fieldId={field.id}
                dayTypes={dayTypes}
                rule={ruleDiedit}
                open={dialogTerbuka}
                onOpenChange={setDialogTerbuka}
            />

            <AlertDialog
                open={ruleDihapus !== undefined}
                onOpenChange={(o) => !o && setRuleDihapus(undefined)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Hapus aturan harga ini?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Aturan {ruleDihapus?.day_label}{' '}
                            {ruleDihapus?.start_time}–{ruleDihapus?.end_time}{' '}
                            akan dihapus. Bila tidak ada aturan lain yang
                            menutupi jam tersebut, slotnya tidak bisa dibooking.
                            Booking yang sudah ada tidak terpengaruh karena
                            harganya sudah disimpan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => {
                                if (ruleDihapus) {
                                    router.delete(destroy(ruleDihapus.id).url, {
                                        preserveScroll: true,
                                    });
                                }

                                setRuleDihapus(undefined);
                            }}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
