import { Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/branches';
import { cities as citiesRoute, districts as districtsRoute } from '@/routes/regions';
import type { BranchFormData, SelectOption } from '@/types';

type BranchFormValues = {
    name: string;
    code: string;
    address: string;
    province_id: string;
    city_id: string;
    district_id: string;
    phone: string;
    operating_hours: {
        weekday: { open: string; close: string };
        weekend: { open: string; close: string };
    };
    photo: File | null;
    is_active: boolean;
};

type Props = {
    provinces: SelectOption<number>[];
    /** Isi awal dropdown bertingkat pada mode edit. */
    cities?: SelectOption<number>[];
    districts?: SelectOption<number>[];
    branch?: BranchFormData;
};

function Wajib() {
    return <span className="text-red-600"> *</span>;
}

export function BranchForm({
    provinces,
    cities: citiesAwal = [],
    districts: districtsAwal = [],
    branch,
}: Props) {
    const [cities, setCities] = useState<SelectOption<number>[]>(citiesAwal);
    const [districts, setDistricts] =
        useState<SelectOption<number>[]>(districtsAwal);
    const renderPertama = useRef(true);

    const form = useForm<BranchFormValues>({
        name: branch?.name ?? '',
        code: branch?.code ?? '',
        address: branch?.address ?? '',
        province_id: branch?.province_id ? String(branch.province_id) : '',
        city_id: branch?.city_id ? String(branch.city_id) : '',
        district_id: branch?.district_id ? String(branch.district_id) : '',
        phone: branch?.phone ?? '',
        operating_hours: branch?.operating_hours ?? {
            weekday: { open: '08:00', close: '23:00' },
            weekend: { open: '08:00', close: '23:00' },
        },
        photo: null,
        is_active: branch?.is_active ?? true,
    });

    const provinceId = form.data.province_id;
    const cityId = form.data.city_id;

    // Ganti provinsi → muat ulang kota, kosongkan kota & kecamatan lama.
    useEffect(() => {
        if (renderPertama.current) {
            return;
        }

        setCities([]);
        setDistricts([]);
        form.setData((data) => ({ ...data, city_id: '', district_id: '' }));

        if (!provinceId) {
            return;
        }

        fetch(citiesRoute({ query: { province_id: provinceId } }).url, {
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            .then(setCities);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [provinceId]);

    // Ganti kota → muat ulang kecamatan, kosongkan kecamatan lama.
    useEffect(() => {
        if (renderPertama.current) {
            renderPertama.current = false;

            return;
        }

        setDistricts([]);
        form.setData((data) => ({ ...data, district_id: '' }));

        if (!cityId) {
            return;
        }

        fetch(districtsRoute({ query: { city_id: cityId } }).url, {
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            .then(setDistricts);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cityId]);

    function setJam(
        tipe: 'weekday' | 'weekend',
        bagian: 'open' | 'close',
        nilai: string,
    ): void {
        form.setData((data) => ({
            ...data,
            operating_hours: {
                ...data.operating_hours,
                [tipe]: { ...data.operating_hours[tipe], [bagian]: nilai },
            },
        }));
    }

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();

        if (branch) {
            // multipart/form-data tidak terbaca lewat PUT, jadi dikirim sebagai
            // POST dengan method spoofing agar Laravel tetap memprosesnya sebagai PUT.
            // Lihat https://inertiajs.com/docs/v3/the-basics/file-uploads
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update(branch.id).url, {
                preserveScroll: true,
                forceFormData: true,
            });

            return;
        }

        form.post(store().url, { preserveScroll: true, forceFormData: true });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Card>
                <CardContent className="space-y-6">
                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">
                                Nama Cabang
                                <Wajib />
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="Contoh: Sportivo Kemang"
                                aria-invalid={Boolean(form.errors.name)}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="code">
                                Kode Cabang
                                <Wajib />
                            </Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData('code', e.target.value)
                                }
                                placeholder="Contoh: JKT01"
                                className="font-mono uppercase"
                                aria-invalid={Boolean(form.errors.code)}
                            />
                            <InputError message={form.errors.code} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="address">
                            Alamat
                            <Wajib />
                        </Label>
                        <Textarea
                            id="address"
                            value={form.data.address}
                            onChange={(e) =>
                                form.setData('address', e.target.value)
                            }
                            placeholder="Masukkan alamat lengkap cabang"
                            rows={3}
                            aria-invalid={Boolean(form.errors.address)}
                        />
                        <InputError message={form.errors.address} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="province_id">Provinsi</Label>
                            <Select
                                value={form.data.province_id}
                                onValueChange={(v) =>
                                    form.setData('province_id', v)
                                }
                            >
                                <SelectTrigger
                                    id="province_id"
                                    aria-invalid={Boolean(
                                        form.errors.province_id,
                                    )}
                                >
                                    <SelectValue placeholder="Pilih provinsi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {provinces.map((p) => (
                                        <SelectItem
                                            key={p.value}
                                            value={String(p.value)}
                                        >
                                            {p.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.province_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="city_id">Kota/Kabupaten</Label>
                            <Select
                                value={form.data.city_id}
                                onValueChange={(v) => form.setData('city_id', v)}
                                disabled={!form.data.province_id}
                            >
                                <SelectTrigger
                                    id="city_id"
                                    aria-invalid={Boolean(form.errors.city_id)}
                                >
                                    <SelectValue
                                        placeholder={
                                            form.data.province_id
                                                ? 'Pilih kota/kabupaten'
                                                : 'Pilih provinsi dulu'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {cities.map((c) => (
                                        <SelectItem
                                            key={c.value}
                                            value={String(c.value)}
                                        >
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.city_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="district_id">Kecamatan</Label>
                            <Select
                                value={form.data.district_id}
                                onValueChange={(v) =>
                                    form.setData('district_id', v)
                                }
                                disabled={!form.data.city_id}
                            >
                                <SelectTrigger
                                    id="district_id"
                                    aria-invalid={Boolean(
                                        form.errors.district_id,
                                    )}
                                >
                                    <SelectValue
                                        placeholder={
                                            form.data.city_id
                                                ? 'Pilih kecamatan'
                                                : 'Pilih kota dulu'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {districts.map((d) => (
                                        <SelectItem
                                            key={d.value}
                                            value={String(d.value)}
                                        >
                                            {d.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.district_id} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">
                            Nomor Telepon
                            <Wajib />
                        </Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
                            placeholder="Contoh: 0211234567"
                            aria-invalid={Boolean(form.errors.phone)}
                        />
                        <InputError message={form.errors.phone} />
                    </div>

                    <fieldset className="space-y-4">
                        <legend className="text-sm font-medium">
                            Jam Operasional
                            <Wajib />
                        </legend>
                        <p className="text-sm text-muted-foreground">
                            Menentukan rentang slot yang tersedia di grid
                            booking.
                        </p>

                        {(['weekday', 'weekend'] as const).map((tipe) => (
                            <div
                                key={tipe}
                                className="grid gap-4 sm:grid-cols-2"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor={`${tipe}_open`}>
                                        {tipe === 'weekday'
                                            ? 'Senin–Jumat buka'
                                            : 'Sabtu–Minggu buka'}
                                    </Label>
                                    <Input
                                        id={`${tipe}_open`}
                                        type="time"
                                        value={form.data.operating_hours[tipe].open}
                                        onChange={(e) =>
                                            setJam(tipe, 'open', e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={
                                            form.errors[
                                                `operating_hours.${tipe}.open` as keyof typeof form.errors
                                            ]
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor={`${tipe}_close`}>
                                        {tipe === 'weekday'
                                            ? 'Senin–Jumat tutup'
                                            : 'Sabtu–Minggu tutup'}
                                    </Label>
                                    <Input
                                        id={`${tipe}_close`}
                                        type="time"
                                        value={
                                            form.data.operating_hours[tipe].close
                                        }
                                        onChange={(e) =>
                                            setJam(tipe, 'close', e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={
                                            form.errors[
                                                `operating_hours.${tipe}.close` as keyof typeof form.errors
                                            ]
                                        }
                                    />
                                </div>
                            </div>
                        ))}
                    </fieldset>

                    <div className="space-y-2">
                        <Label htmlFor="photo">Foto Cabang</Label>
                        {branch?.photo_url && (
                            <img
                                src={branch.photo_url}
                                alt={`Foto ${branch.name}`}
                                className="h-32 w-full max-w-xs rounded-md object-cover"
                            />
                        )}
                        <Input
                            id="photo"
                            type="file"
                            accept="image/*"
                            onChange={(e) =>
                                form.setData(
                                    'photo',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                            aria-invalid={Boolean(form.errors.photo)}
                        />
                        <p className="text-sm text-muted-foreground">
                            Opsional. Maksimal 2 MB.
                            {branch?.photo_url &&
                                ' Kosongkan bila tidak ingin mengganti.'}
                        </p>
                        <InputError message={form.errors.photo} />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={form.data.is_active}
                                onCheckedChange={(c) =>
                                    form.setData('is_active', c)
                                }
                            />
                            <Label htmlFor="is_active">Cabang aktif</Label>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Cabang nonaktif tidak muncul di halaman booking
                            publik.
                        </p>
                        <InputError message={form.errors.is_active} />
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
