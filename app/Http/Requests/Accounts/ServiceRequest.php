<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isAccountant());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:domain,hosting,web_development,digital_marketing'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Service name is required',
            'type.required' => 'Service type is required',
            'type.in' => 'Invalid service type',
            'default_price.required' => 'Default price is required',
            'default_price.numeric' => 'Default price must be a number',
            'default_price.min' => 'Default price cannot be negative',
        ];
    }
}
