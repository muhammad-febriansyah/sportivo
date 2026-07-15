<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Field;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Perhatikan: TIDAK ada field harga di sini.
 *
 * Seluruh harga dihitung server lewat PricingService — kasir tidak pernah
 * mengetik harga, dan klien tidak bisa mengirimkannya (docs/01-prd.md Modul 4).
 */
class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'field_id' => ['required', 'integer', Rule::exists('fields', 'id')->whereNull('deleted_at')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'pay_full' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'field_id' => 'lapangan',
            'customer_id' => 'pelanggan',
            'booking_date' => 'tanggal',
            'start_time' => 'jam mulai',
            'duration_hours' => 'durasi',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Kasir/admin hanya boleh membuat booking di cabangnya sendiri.
                // Tanpa ini, mengganti field_id di payload menembus batas cabang.
                if ($this->user()->isOwner()) {
                    return;
                }

                $field = Field::find($this->input('field_id'));

                if ($field === null) {
                    return;
                }

                if ($field->branch_id !== $this->user()->branch_id) {
                    $validator->errors()->add(
                        'field_id',
                        'Anda hanya dapat membuat booking di lapangan cabang Anda sendiri.'
                    );
                }
            },
        ];
    }
}
