<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:expenses.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'paid_via' => ['required', Rule::in(Expense::PAID_VIA)],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
        ];
    }
}
