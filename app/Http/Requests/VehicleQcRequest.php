<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleQcRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle-qc');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date',
            'make_code_id' => 'required|exists:make_codes,id',
            'car_model_id' => 'required|exists:car_models,id',
        ];
    }
}
