import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';

/**
 * Semua nominal Rupiah adalah integer tanpa desimal (BIGINT di database).
 * Lihat docs/05-tech-conventions.md.
 */
export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

/**
 * Versi ringkas untuk sel grid booking yang sempit: "Rp 250rb", "Rp 1,2jt".
 */
export function formatRupiahRingkas(value: number): string {
    if (value >= 1_000_000) {
        const juta = value / 1_000_000;
        const teks = juta.toFixed(juta % 1 === 0 ? 0 : 1).replace('.', ',');

        return `Rp ${teks}jt`;
    }

    if (value >= 1_000) {
        return `Rp ${Math.round(value / 1_000)}rb`;
    }

    return `Rp ${value}`;
}

/**
 * Untuk kolom DATE murni (contoh `bookings.booking_date` = "2026-07-15").
 * Kolom ini wall-clock WIB dan tidak menyimpan timezone, jadi tidak dikonversi.
 */
export function formatTanggal(value: string | Date): string {
    const tanggal = typeof value === 'string' ? parseISO(value) : value;

    return format(tanggal, 'dd MMM yyyy', { locale: id });
}

/**
 * Untuk kolom TIME murni (contoh `bookings.start_time` = "19:00:00") → "19:00".
 * Sama seperti formatTanggal: wall-clock WIB, tanpa konversi.
 */
export function formatJam(value: string): string {
    return value.slice(0, 5);
}

/**
 * Untuk kolom TIMESTAMP yang disimpan UTC (created_at, paid_at, expired_at, dst).
 * Dikonversi ke WIB lewat Intl agar hasilnya benar walau timezone browser bukan WIB.
 * Lihat docs/01-prd.md NFR 2.
 */
export function formatWaktuWib(value: string | Date): string {
    const waktu = typeof value === 'string' ? new Date(value) : value;

    return new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(waktu);
}

/**
 * Nilai yang dikirim ke server untuk input tanggal: "YYYY-MM-DD".
 */
export function toTanggalServer(value: Date): string {
    return format(value, 'yyyy-MM-dd');
}
