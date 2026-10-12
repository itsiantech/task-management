<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

/**
 * Payment recorded against a specific invoice (nested route).
 * The invoice comes from the URL, so no invoice_id/client_id is required.
 */
class InvoicePaymentRequest extends PaymentRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['invoice_id'] = ['nullable', 'exists:invoices,id'];
        $rules['client_id'] = ['nullable', 'exists:clients,id'];

        return $rules;
    }
}
