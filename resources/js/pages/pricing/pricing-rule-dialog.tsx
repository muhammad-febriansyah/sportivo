import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { RupiahInput } from '@/components/rupiah-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/fields/pricing';
import { update } from '@/routes/pricing';
import type { DayType, PricingRuleRow, SelectOption } from '@/types';

type PricingFormValues = {
    day_type: DayType | '';
    start_time: string;
    end_time: string;
    price: number | '';
    member_price: number | '';
};

type Props = {
    fieldId: number;
    dayTypes: SelectOption<DayType>[];
    /** Kosongkan untuk mode tambah. */
    rule?: PricingRuleRow;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger?: ReactNode;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

/**
 * Form harga hanya 5 field, jadi memakai modal — lihat docs/04-design-system.md
 * bagian Form Rules.
 */
export function PricingRuleDialog({
    fieldId,
    dayTypes,
    rule,
    open,
    onOpenChange,
    trigger,
}: Props) {
    const form = useForm<PricingFormValues>({
        day_type: rule?.day_type ?? '',
        start_time: rule?.start_time ?? '08:00',
        end_time: rule?.end_time ?? '17:00',
        price: rule?.price ?? '',
        member_price: rule?.member_price ?? '',
    });

    // Modal dipakai ulang antar baris; isinya harus ikut baris yang dibuka.
    useEffect(() => {
        if (!open) {
            return;
        }

        form.setDefaults({
            day_type: rule?.day_type ?? '',
            start_time: rule?.start_time ?? '08:00',
            end_time: rule?.end_time ?? '17:00',
            price: rule?.price ?? '',
            member_price: rule?.member_price ?? '',
        });
        form.reset();
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, rule?.id]);

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        const opsi = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (rule) {
            form.put(update(rule.id).url, opsi);

            return;
        }

        form.post(store(fieldId).url, opsi);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}

            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {rule ? 'Edit Aturan Harga' : 'Tambah Aturan Harga'}
                        </DialogTitle>
                        <DialogDescription>
                            Harga berlaku per jam. Aturan hari spesifik menang
                            atas weekday/weekend.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="day_type">
                                Tipe Hari
                                <Wajib />
                            </Label>
                            <Select
                                value={form.data.day_type}
                                onValueChange={(v) =>
                                    form.setData('day_type', v as DayType)
                                }
                            >
                                <SelectTrigger
                                    id="day_type"
                                    aria-invalid={Boolean(form.errors.day_type)}
                                >
                                    <SelectValue placeholder="Pilih tipe hari" />
                                </SelectTrigger>
                                <SelectContent>
                                    {dayTypes.map((d) => (
                                        <SelectItem key={d.value} value={d.value}>
                                            {d.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.day_type} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="start_time">
                                    Jam Mulai
                                    <Wajib />
                                </Label>
                                <Input
                                    id="start_time"
                                    type="time"
                                    value={form.data.start_time}
                                    onChange={(e) =>
                                        form.setData('start_time', e.target.value)
                                    }
                                    aria-invalid={Boolean(form.errors.start_time)}
                                />
                                <InputError message={form.errors.start_time} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="end_time">
                                    Jam Selesai
                                    <Wajib />
                                </Label>
                                <Input
                                    id="end_time"
                                    type="time"
                                    value={form.data.end_time}
                                    onChange={(e) =>
                                        form.setData('end_time', e.target.value)
                                    }
                                    aria-invalid={Boolean(form.errors.end_time)}
                                />
                                <InputError message={form.errors.end_time} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="price">
                                Harga per Jam
                                <Wajib />
                            </Label>
                            <RupiahInput
                                id="price"
                                value={form.data.price}
                                onChange={(v) => form.setData('price', v)}
                                aria-invalid={Boolean(form.errors.price)}
                            />
                            <InputError message={form.errors.price} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="member_price">Harga Member</Label>
                            <RupiahInput
                                id="member_price"
                                value={form.data.member_price}
                                onChange={(v) => form.setData('member_price', v)}
                                aria-invalid={Boolean(form.errors.member_price)}
                            />
                            <p className="text-sm text-muted-foreground">
                                Kosongkan bila member memakai harga umum.
                            </p>
                            <InputError message={form.errors.member_price} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
