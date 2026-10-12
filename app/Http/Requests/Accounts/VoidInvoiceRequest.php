<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class VoidInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isAccountant());
    }

    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'void_reason.required' => 'A void reason is mandatory and will be audit-logged.',
        ];
    }
}
