<?php

namespace App\Http\Requests;

use App\Concerns\BranchValidationRules;
use App\Models\Branch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBranchRequest extends FormRequest
{
    use BranchValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Branch::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->branchRules(),
            // Cabang yang di-soft-delete tetap memegang kode-nya agar data
            // historis tidak ambigu, jadi unique tidak mengabaikan deleted_at.
            'code' => ['required', 'string', 'max:10', Rule::unique('branches', 'code')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->branchAttributes();
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateRegionCascade($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper(trim((string) $this->input('code'))),
        ]);
    }
}
