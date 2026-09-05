<?php

namespace App\Http\Requests;

use App\Models\EventPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:events.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(EventPayment::TYPES)],
            'method' => ['required', Rule::in(EventPayment::METHODS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
