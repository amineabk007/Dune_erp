<?php

namespace App\Http\Requests;

use App\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:tables.manage' route middleware
    }

    public function rules(): array
    {
        $table = $this->route('table');

        return [
            'zone_id' => ['required', 'exists:zones,id'],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('restaurant_tables')
                    ->where(fn ($q) => $q->where('zone_id', $this->input('zone_id')))
                    ->ignore($table?->id),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::in(RestaurantTable::STATUSES)],
        ];
    }
}
