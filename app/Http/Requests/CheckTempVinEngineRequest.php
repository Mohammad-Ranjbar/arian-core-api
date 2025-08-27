<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckTempVinEngineRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_order_number' => 'required|string|max:255|exists:vehicles,production_order_number',
            'engine_no' => 'nullable|string|max:255',
        ];
    }
}
