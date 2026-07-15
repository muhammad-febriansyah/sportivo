<?php

namespace App\Http\Requests;

use App\Concerns\FieldValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldRequest extends FormRequest
{
    use FieldValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('field'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->fieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->fieldAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->fieldMessages();
    }

    protected function prepareForValidation(): void
    {
        // Admin selalu terkunci ke cabangnya sendiri, apa pun isi payload.
        if (! $this->user()->isOwner()) {
            $this->merge(['branch_id' => $this->user()->branch_id]);
        }
    }
}
