<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:stock.adjust' route middleware
    }

    /**
     * Note: current_stock is deliberately not editable here. Stock quantity
     * only ever changes through a recorded movement (adjustment, inventory,
     * consumption, purchase...), never by silently overwriting the field.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ingredients', 'name')->ignore($this->route('ingredient'))],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999.999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
