<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isAccountant());
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'exists:invoices,id', 'required_without:client_id'],
            'client_id' => ['nullable', 'exists:clients,id', 'required_without:invoice_id'],
            'order_id' => ['nullable', 'exists:tasks,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'in:bkash,nagad,bank,cash'],
            'receiving_account' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required_without' => 'Select an invoice or a client for this payment.',
            'client_id.required_without' => 'Select an invoice or a client for this payment.',
        ];
    }
}
