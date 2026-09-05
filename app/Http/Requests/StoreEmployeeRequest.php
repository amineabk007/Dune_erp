<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:employees.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'hire_date' => ['required', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'user_id' => ['nullable', 'exists:users,id', 'unique:employees,user_id'],
        ];
    }
}
