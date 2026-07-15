<?php

namespace App\Http\Requests;

use App\Concerns\AddonValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddonRequest extends FormRequest
{
    use AddonValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('addon'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->addonRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->addonAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->addonMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->lockAddonBranch();
    }
}
