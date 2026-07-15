import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type RupiahInputProps = {
    value: number | '';
    onChange: (value: number | '') => void;
    placeholder?: string;
    id?: string;
    disabled?: boolean;
    className?: string;
    'aria-invalid'?: boolean;
};

/**
 * Input nominal Rupiah. Wajib dipakai untuk semua input uang.
 *
 * - Menampilkan "Rp 250.000" (pemisah ribuan titik, tanpa desimal).
 * - Mengirim integer murni ke server (250000).
 * - Paste "Rp 1.500.000" ikut terparse jadi 1500000.
 *
 * Lihat docs/04-design-system.md.
 */
export function RupiahInput({
    value,
    onChange,
    placeholder = '0',
    id,
    disabled,
    className,
    'aria-invalid': ariaInvalid,
}: RupiahInputProps) {
    const tampilan =
        value === ''
            ? ''
            : `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

    function handleChange(event: React.ChangeEvent<HTMLInputElement>): void {
        const digit = event.target.value.replace(/\D/g, '');

        if (digit === '') {
            onChange('');

            return;
        }

        onChange(Number.parseInt(digit, 10));
    }

    return (
        <Input
            id={id}
            type="text"
            inputMode="numeric"
            value={tampilan}
            onChange={handleChange}
            placeholder={placeholder}
            disabled={disabled}
            aria-invalid={ariaInvalid}
            className={cn(className)}
        />
    );
}
