<?php

namespace App\Concerns;

use App\Enums\FieldStatus;
use App\Enums\SurfaceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FieldValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'surface_type' => ['required', Rule::enum(SurfaceType::class)],
            'size' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(FieldStatus::class)],
            'photo' => ['nullable', 'image', 'max:2048'],

            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->whereNull('deleted_at'),
                // Admin tidak boleh menaruh lapangan di cabang milik orang lain.
                // Tanpa aturan ini, cukup mengganti branch_id di payload untuk
                // menembus pembatasan cabang.
                Rule::when(
                    ! $this->user()->isOwner(),
                    [Rule::in([$this->user()->branch_id])]
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldAttributes(): array
    {
        return [
            'name' => 'nama lapangan',
            'branch_id' => 'cabang',
            'surface_type' => 'tipe rumput',
            'size' => 'ukuran',
            'description' => 'deskripsi',
            'status' => 'status',
            'photo' => 'foto',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldMessages(): array
    {
        return [
            'branch_id.in' => 'Anda hanya dapat mengelola lapangan di cabang Anda sendiri.',
        ];
    }
}
