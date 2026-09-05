<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:orders.discount' route middleware
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
