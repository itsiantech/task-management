<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\PaymentAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $types = array_map(fn (PaymentAccountType $t) => $t->value, PaymentAccountType::cases());
        $ignore = $this->route('payment_account')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_accounts', 'name')->ignore($ignore)],
            'type' => ['required', 'string', Rule::in($types)],
            'account_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
