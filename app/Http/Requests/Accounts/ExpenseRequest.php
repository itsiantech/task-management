<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isAccountant());
    }

    public function rules(): array
    {
        $categories = array_map(fn (ExpenseCategory $c) => $c->value, ExpenseCategory::cases());
        $methods = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases());

        return [
            'category' => ['required', 'string', Rule::in($categories)],
            'custom_category' => ['nullable', 'string', 'max:255', 'required_if:category,custom'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'paid_via' => ['required', 'string', Rule::in($methods)],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'payable_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'is_paid' => ['nullable', 'boolean'],
        ];
    }
}
