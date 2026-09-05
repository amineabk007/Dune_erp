<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:stock.inventory' route middleware
    }

    public function rules(): array
    {
        return [
            'counted_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
