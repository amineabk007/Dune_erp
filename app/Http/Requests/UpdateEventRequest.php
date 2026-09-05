<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:events.manage' route middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
