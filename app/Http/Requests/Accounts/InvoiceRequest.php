<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isAccountant());
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'advance_paid_credit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_method' => ['nullable', 'string', 'in:bkash,nagad,bank,cash,cash_in'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'billing_company' => ['nullable', 'string', 'max:255'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'billing_phone' => ['nullable', 'string', 'max:50'],
            'billing_email' => ['nullable', 'string', 'max:255'],
            'billing_country' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client. Use "Create Client" if the client does not exist yet.',
            'items.required' => 'Add at least one invoice item.',
            'items.min' => 'Add at least one invoice item.',
        ];
    }
}
