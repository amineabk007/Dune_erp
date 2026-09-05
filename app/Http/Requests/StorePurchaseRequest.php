<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:purchases.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'ingredient_id' => ['required', 'array', 'min:1'],
            'ingredient_id.*' => ['integer', 'exists:ingredients,id', 'distinct'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.001'],
            'unit_cost' => ['required', 'array', 'min:1'],
            'unit_cost.*' => ['numeric', 'min:0'],
        ];
    }
}
