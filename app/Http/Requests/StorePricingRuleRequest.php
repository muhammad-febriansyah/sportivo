<?php

namespace App\Http\Requests;

use App\Concerns\PricingRuleValidationRules;
use App\Models\Field;
use App\Models\PricingRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePricingRuleRequest extends FormRequest
{
    use PricingRuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [PricingRule::class, $this->route('field')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->pricingRuleRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->pricingRuleAttributes();
    }

    public function after(): array
    {
        /** @var Field $field */
        $field = $this->route('field');

        return [
            fn (Validator $validator) => $this->validateNoOverlap($validator, $field->id),
        ];
    }
}
