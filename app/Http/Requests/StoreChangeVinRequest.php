<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChangeVinRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('change-vins.store');
    }


    public function rules(): array
    {
        return [
            'vin' => 'required|string|max:255',
            'old_vin' => 'nullable|string|max:255',
            'production_order_number' => 'required|string|max:255',
        ];
    }
}
