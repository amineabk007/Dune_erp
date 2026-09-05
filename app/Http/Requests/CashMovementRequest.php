<?php

namespace App\Http\Requests;

use App\Models\CashMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:cash.movement' route middleware
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(CashMovement::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
