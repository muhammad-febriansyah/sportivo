import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { search as searchRoute } from '@/routes/customers';
import type { CustomerSearchResult } from '@/types';

type Props = {
    id?: string;
    value: CustomerSearchResult | null;
    onSelect: (customer: CustomerSearchResult | null) => void;
    'aria-invalid'?: boolean;
};

/**
 * Pencarian pelanggan by nomor WA untuk form booking walk-in.
 * Kasir harus bisa menemukan pelanggan dalam hitungan detik (US-05).
 */
export function CustomerSearch({
    id = 'customer_search',
    value,
    onSelect,
    'aria-invalid': ariaInvalid,
}: Props) {
    const [keyword, setKeyword] = useState('');
    const [hasil, setHasil] = useState<CustomerSearchResult[]>([]);
    const [terbuka, setTerbuka] = useState(false);
    const wadah = useRef<HTMLDivElement>(null);

    // Debounce agar tiap ketikan tidak memicu request.
    useEffect(() => {
        if (keyword.trim().length < 2) {
            // Dibungkus timer agar tidak setState sinkron di dalam effect —
            // itu memicu cascading render.
            const bersihkan = setTimeout(() => setHasil([]), 0);

            return () => clearTimeout(bersihkan);
        }

        const timer = setTimeout(() => {
            fetch(searchRoute({ query: { q: keyword } }).url, {
                headers: { Accept: 'application/json' },
            })
                .then((res) => res.json())
                .then((data: CustomerSearchResult[]) => {
                    setHasil(data);
                    setTerbuka(true);
                })
                .catch(() => setHasil([]));
        }, 300);

        return () => clearTimeout(timer);
    }, [keyword]);

    // Tutup daftar saat klik di luar.
    useEffect(() => {
        function handleKlik(e: MouseEvent): void {
            if (wadah.current && !wadah.current.contains(e.target as Node)) {
                setTerbuka(false);
            }
        }

        document.addEventListener('mousedown', handleKlik);

        return () => document.removeEventListener('mousedown', handleKlik);
    }, []);

    if (value) {
        return (
            <div className="flex items-center justify-between rounded-md border p-3">
                <div>
                    <p className="font-medium">
                        {value.name}
                        {value.is_member && (
                            <Badge className="ml-2 bg-green-600 text-white">
                                Member
                            </Badge>
                        )}
                    </p>
                    <p className="font-mono text-sm text-muted-foreground">
                        {value.phone}
                    </p>
                </div>
                <button
                    type="button"
                    className="text-sm text-red-600 hover:underline"
                    onClick={() => {
                        onSelect(null);
                        setKeyword('');
                    }}
                >
                    Ganti
                </button>
            </div>
        );
    }

    return (
        <div ref={wadah} className="relative">
            <Input
                id={id}
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
                onFocus={() => hasil.length > 0 && setTerbuka(true)}
                placeholder="Cari nama atau nomor WhatsApp"
                autoComplete="off"
                aria-invalid={ariaInvalid}
            />

            {terbuka && hasil.length > 0 && (
                <ul className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover shadow-md">
                    {hasil.map((c) => (
                        <li key={c.id}>
                            <button
                                type="button"
                                className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-accent"
                                onClick={() => {
                                    onSelect(c);
                                    setTerbuka(false);
                                }}
                            >
                                <span>{c.name}</span>
                                <span className="font-mono text-xs text-muted-foreground">
                                    {c.phone}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {terbuka && keyword.trim().length >= 2 && hasil.length === 0 && (
                <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover p-3 text-sm text-muted-foreground shadow-md">
                    Pelanggan tidak ditemukan.
                </div>
            )}
        </div>
    );
}
