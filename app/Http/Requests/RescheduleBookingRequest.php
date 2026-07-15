<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Field;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RescheduleBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            // Kosongkan untuk tetap di lapangan yang sama.
            'field_id' => ['nullable', 'integer', Rule::exists('fields', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'booking_date' => 'tanggal baru',
            'start_time' => 'jam mulai baru',
            'field_id' => 'lapangan',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fieldId = $this->input('field_id');

                if (! $fieldId) {
                    return;
                }

                /** @var Booking $booking */
                $booking = $this->route('booking');

                // Reschedule hanya boleh berpindah lapangan di dalam cabang yang
                // sama — pindah cabang mengubah harga, pengaturan DP, dan
                // kepemilikan data, jadi itu booking baru, bukan reschedule.
                if (Field::find($fieldId)?->branch_id !== $booking->branch_id) {
                    $validator->errors()->add(
                        'field_id',
                        'Lapangan tujuan harus berada di cabang yang sama.'
                    );
                }
            },
        ];
    }
}
