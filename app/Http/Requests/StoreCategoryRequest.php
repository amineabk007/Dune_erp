<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:categories.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'type' => ['required', Rule::in(Category::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
