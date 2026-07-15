<?php

namespace App\Http\Requests;

use App\Concerns\CustomerValidationRules;
use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    use CustomerValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->customerRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->customerAttributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->customerMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeCustomerPhone();

        // Kasir tidak boleh menetapkan status member lewat payload.
        if ($this->user()->cannot('manageMembership', new Customer)) {
            $this->merge(['is_member' => false, 'member_until' => null]);
        }
    }
}
