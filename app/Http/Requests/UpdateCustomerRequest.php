<?php

namespace App\Http\Requests;

use App\Concerns\CustomerValidationRules;
use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    use CustomerValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Customer $customer */
        $customer = $this->route('customer');

        return $this->customerRules($customer);
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

        // Tanggal berakhir tidak bermakna bila statusnya bukan member.
        if (! $this->boolean('is_member')) {
            $this->merge(['member_until' => null]);
        }
    }
}
