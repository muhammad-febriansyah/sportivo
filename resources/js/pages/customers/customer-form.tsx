import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { DatePickerField } from '@/components/date-picker-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/customers';
import type { CustomerFormData } from '@/types';

type CustomerFormValues = {
    name: string;
    phone: string;
    email: string;
    is_member: boolean;
    member_until: string;
    notes: string;
};

type Props = {
    customer?: CustomerFormData;
    canManageMembership: boolean;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export function CustomerForm({ customer, canManageMembership }: Props) {
    const form = useForm<CustomerFormValues>({
        name: customer?.name ?? '',
        phone: customer?.phone ?? '',
        email: customer?.email ?? '',
        is_member: customer?.is_member ?? false,
        member_until: customer?.member_until ?? '',
        notes: customer?.notes ?? '',
    });

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        if (customer) {
            form.put(update(customer.id).url, { preserveScroll: true });

            return;
        }

        form.post(store().url, { preserveScroll: true });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Card>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Nama
                            <Wajib />
                        </Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Masukkan nama pelanggan"
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">
                            Nomor WhatsApp
                            <Wajib />
                        </Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) =>
                                form.setData('phone', e.target.value)
                            }
                            placeholder="Contoh: 081234567890"
                            aria-invalid={Boolean(form.errors.phone)}
                        />
                        <p className="text-sm text-muted-foreground">
                            Nomor ini adalah identitas unik pelanggan; formatnya
                            otomatis disamakan menjadi 628xxx.
                        </p>
                        <InputError message={form.errors.phone} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                            placeholder="Opsional"
                            aria-invalid={Boolean(form.errors.email)}
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    {canManageMembership && (
                        <>
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <Switch
                                        id="is_member"
                                        checked={form.data.is_member}
                                        onCheckedChange={(c) =>
                                            form.setData('is_member', c)
                                        }
                                    />
                                    <Label htmlFor="is_member">Member</Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Member mendapat harga khusus bila aturan
                                    harga lapangan mengaturnya.
                                </p>
                            </div>

                            {form.data.is_member && (
                                <DatePickerField
                                    id="member_until"
                                    label="Berlaku sampai"
                                    value={form.data.member_until}
                                    onChange={(v) =>
                                        form.setData('member_until', v)
                                    }
                                    disablePastDates
                                    error={form.errors.member_until}
                                    placeholder="Kosongkan untuk tanpa batas"
                                />
                            )}
                        </>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="notes">Catatan Internal</Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                            placeholder="Catatan untuk kasir, tidak terlihat pelanggan"
                            rows={3}
                            aria-invalid={Boolean(form.errors.notes)}
                        />
                        <InputError message={form.errors.notes} />
                    </div>
                </CardContent>

                <CardFooter className="justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href={index()}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
