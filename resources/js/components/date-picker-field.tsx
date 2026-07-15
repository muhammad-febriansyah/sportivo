import { id as localeId } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatTanggal, toTanggalServer } from '@/lib/format';
import { cn } from '@/lib/utils';

type DatePickerFieldProps = {
    id: string;
    label: string;
    /** Format "YYYY-MM-DD", atau string kosong bila belum dipilih. */
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
    error?: string;
    placeholder?: string;
    /** Nonaktifkan tanggal lampau — pakai untuk konteks booking. */
    disablePastDates?: boolean;
    disabled?: boolean;
};

/**
 * Input tanggal standar aplikasi: Calendar + Popover.
 *
 * Menampilkan "15 Jul 2026" (locale id), mengirim "2026-07-15" ke server.
 * Lihat docs/04-design-system.md.
 */
export function DatePickerField({
    id,
    label,
    value,
    onChange,
    required,
    error,
    placeholder = 'Pilih tanggal',
    disablePastDates,
    disabled,
}: DatePickerFieldProps) {
    const [terbuka, setTerbuka] = useState(false);

    const tanggalTerpilih = value ? new Date(`${value}T00:00:00`) : undefined;

    // Batas "hari ini" dinormalisasi ke tengah malam lokal agar tanggal hari ini
    // tidak ikut ter-disable oleh perbandingan jam.
    const hariIni = new Date();
    hariIni.setHours(0, 0, 0, 0);

    function handleSelect(tanggal: Date | undefined): void {
        onChange(tanggal ? toTanggalServer(tanggal) : '');
        setTerbuka(false);
    }

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>
                {label}
                {required && <span className="text-red-600"> *</span>}
            </Label>

            <Popover open={terbuka} onOpenChange={setTerbuka}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-invalid={Boolean(error)}
                        className={cn(
                            'w-full justify-start text-left font-normal',
                            !value && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon className="size-4" />
                        {value ? formatTanggal(value) : placeholder}
                    </Button>
                </PopoverTrigger>

                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        locale={localeId}
                        selected={tanggalTerpilih}
                        onSelect={handleSelect}
                        disabled={
                            disablePastDates ? { before: hariIni } : undefined
                        }
                        autoFocus
                    />
                </PopoverContent>
            </Popover>

            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}
