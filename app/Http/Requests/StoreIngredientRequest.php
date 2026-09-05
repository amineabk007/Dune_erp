<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:stock.adjust' route middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name'],
            'unit' => ['required', 'string', 'max:20'],
            'current_stock' => ['required', 'numeric', 'min:0', 'max:999999.999'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999.999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
