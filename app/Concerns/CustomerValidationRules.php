<?php

namespace App\Concerns;

use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CustomerValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function customerRules(?Customer $customer = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Unik diperiksa setelah normalisasi — lihat prepareForValidation.
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique('customers', 'phone')->ignore($customer?->id)->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'is_member' => ['boolean'],
            // Tanggal berakhir hanya relevan bila statusnya member.
            'member_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function customerAttributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor WhatsApp',
            'email' => 'email',
            'is_member' => 'status member',
            'member_until' => 'berlaku sampai',
            'notes' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function customerMessages(): array
    {
        return [
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar atas pelanggan lain.',
        ];
    }

    /**
     * Normalisasi nomor SEBELUM validasi unique, agar "08123" dan "628123"
     * tidak lolos sebagai dua pelanggan berbeda.
     */
    protected function normalizeCustomerPhone(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->input('phone'))]);
        }
    }
}
