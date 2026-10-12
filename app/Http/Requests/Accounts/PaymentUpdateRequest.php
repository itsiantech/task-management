<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

class PaymentUpdateRequest extends PaymentRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // On update the payment already exists, so the invoice/client link
        // is optional here (it is only mandatory when recording a payment).
        $rules['invoice_id'] = ['nullable', 'exists:invoices,id'];
        $rules['client_id'] = ['nullable', 'exists:clients,id'];

        return array_merge($rules, [
            // Mandatory correction note: who/when/old/new/reason is stored in payment_edits.
            'edit_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'edit_reason.required' => 'A correction note is mandatory when editing a payment.',
            'edit_reason.min' => 'Please explain the correction (at least 5 characters).',
        ]);
    }
}
