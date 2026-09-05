<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:reservations.update' route middleware
    }

    public function rules(): array
    {
        return [
            'reserved_at' => ['required', 'date'],
            'guests' => ['required', 'integer', 'min:1', 'max:100'],
            'table_ids' => ['required', 'array', 'min:1'],
            'table_ids.*' => ['integer', 'exists:restaurant_tables,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
