<?php

namespace App\Support;

/**
 * Normalisasi nomor WhatsApp ke bentuk 628xxx.
 *
 * Nomor WA adalah identifier unik pelanggan (docs/02-erd.md), jadi bentuknya
 * harus tunggal. Tanpa ini "08123", "628123", dan "+62 812-3" akan menjadi tiga
 * pelanggan berbeda padahal orangnya sama.
 *
 * Lihat docs/05-tech-conventions.md bagian Validasi & Bahasa.
 */
class PhoneNumber
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Buang semua kecuali digit: spasi, tanda hubung, kurung, tanda plus.
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        // 08xxx → 628xxx
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        // 8xxx → 628xxx (user mengetik tanpa 0 maupun 62)
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
