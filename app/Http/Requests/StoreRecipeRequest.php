<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:recipes.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id', 'unique:recipes,product_id'],
            'yield_quantity' => ['required', 'numeric', 'min:0.01'],
            'instructions' => ['nullable', 'string'],
            'ingredient_id' => ['required', 'array', 'min:1'],
            'ingredient_id.*' => ['integer', 'exists:ingredients,id', 'distinct'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.001'],
        ];
    }
}
