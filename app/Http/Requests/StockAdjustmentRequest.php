<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:stock.adjust' route middleware
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['adjustment', 'waste', 'return', 'transfer'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'direction' => ['required', Rule::in(['in', 'out'])],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
