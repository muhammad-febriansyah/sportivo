<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(UserRole::class)],
            // Owner tidak terikat cabang; admin & kasir wajib punya cabang.
            'branch_id' => [
                Rule::requiredIf(fn (): bool => $this->input('role') !== UserRole::Owner->value),
                'nullable',
                Rule::exists('branches', 'id')->whereNull('deleted_at'),
            ],
            'is_active' => ['boolean'],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'nomor WhatsApp',
            'role' => 'role',
            'branch_id' => 'cabang',
            'password' => 'kata sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih untuk role admin dan kasir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') === UserRole::Owner->value) {
            $this->merge(['branch_id' => null]);
        }
    }
}
