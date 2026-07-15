<?php

namespace App\Http\Requests;

use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Field;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBlockedSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', BlockedSlot::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->whereNull('deleted_at')],
            // Null = blokir seluruh lapangan di cabang tersebut (US-10).
            'field_id' => ['nullable', 'integer', Rule::exists('fields', 'id')->whereNull('deleted_at')],
            'block_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'cabang',
            'field_id' => 'lapangan',
            'block_date' => 'tanggal',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'reason' => 'alasan',
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $v) => $this->validateBranchScope($v),
            fn (Validator $v) => $this->validateNoActiveBookings($v),
        ];
    }

    /**
     * Admin tidak boleh memblokir cabang orang lain, apa pun isi payload.
     */
    private function validateBranchScope(Validator $validator): void
    {
        if ($this->user()->isOwner()) {
            return;
        }

        if ((int) $this->input('branch_id') !== $this->user()->branch_id) {
            $validator->errors()->add('branch_id', 'Anda hanya dapat memblokir slot di cabang Anda sendiri.');

            return;
        }

        $fieldId = $this->input('field_id');

        if ($fieldId && Field::find($fieldId)?->branch_id !== $this->user()->branch_id) {
            $validator->errors()->add('field_id', 'Lapangan tersebut bukan milik cabang Anda.');
        }
    }

    /**
     * Slot dengan booking aktif tidak boleh diblokir — admin harus me-reschedule
     * atau membatalkan booking tersebut lebih dulu (docs/01-prd.md Modul 12).
     *
     * Daftar booking yang bentrok ikut disebut agar admin tahu apa yang harus
     * dibereskan, bukan sekadar ditolak.
     */
    private function validateNoActiveBookings(Validator $validator): void
    {
        $date = $this->input('block_date');
        $start = $this->input('start_time');
        $end = $this->input('end_time');

        if (! $date || ! $start || ! $end || $validator->errors()->isNotEmpty()) {
            return;
        }

        $fieldId = $this->input('field_id');

        $bentrok = Booking::query()
            ->where('branch_id', $this->input('branch_id'))
            ->when($fieldId, fn ($q, $id) => $q->where('field_id', $id))
            ->whereDate('booking_date', Carbon::parse($date))
            ->holdingSlot()
            ->where(fn ($q) => $q
                ->where('start_time', '<', Booking::normalizeTime($end))
                ->where('end_time', '>', Booking::normalizeTime($start))
            )
            ->get(['code', 'field_name', 'start_time', 'end_time', 'customer_name']);

        if ($bentrok->isEmpty()) {
            return;
        }

        $daftar = $bentrok
            ->map(fn (Booking $b): string => sprintf(
                '%s (%s %s–%s, %s)',
                $b->code,
                $b->field_name,
                substr($b->start_time, 0, 5),
                substr($b->end_time, 0, 5),
                $b->customer_name,
            ))
            ->implode('; ');

        $validator->errors()->add('start_time', sprintf(
            'Masih ada %d booking aktif di rentang ini: %s. Reschedule atau batalkan booking tersebut lebih dulu.',
            $bentrok->count(),
            $daftar,
        ));
    }
}
