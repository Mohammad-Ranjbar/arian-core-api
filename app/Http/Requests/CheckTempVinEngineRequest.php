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
            'production_order_number' => 'required|exists:vehicles,production_order_number',
            'engine_no' => 'nullable|exists:vin_engines,engine_no',
        ];
    }
}
