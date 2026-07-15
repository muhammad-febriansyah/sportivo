<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AddonValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function addonRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Rupiah selalu integer tanpa desimal.
            'price' => ['required', 'integer', 'min:0'],
            // Null = stok tidak dibatasi (docs/02-erd.md).
            'stock' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->whereNull('deleted_at'),
                // Admin tidak boleh menaruh add-on di cabang orang lain.
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
    protected function addonAttributes(): array
    {
        return [
            'name' => 'nama add-on',
            'price' => 'harga',
            'stock' => 'stok',
            'branch_id' => 'cabang',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function addonMessages(): array
    {
        return [
            'branch_id.in' => 'Anda hanya dapat mengelola add-on di cabang Anda sendiri.',
        ];
    }

    /**
     * Admin selalu terkunci ke cabangnya, apa pun isi payload.
     */
    protected function lockAddonBranch(): void
    {
        if (! $this->user()->isOwner()) {
            $this->merge(['branch_id' => $this->user()->branch_id]);
        }
    }
}
