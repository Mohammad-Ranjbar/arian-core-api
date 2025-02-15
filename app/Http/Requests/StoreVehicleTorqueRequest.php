<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleTorqueRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('vehicles.torque-test');
    }


    public function rules(): array
    {
        return [
            'vin' => 'required|string|exists:vehicles,vin',
            'value' => 'required|numeric',
            'screw_id' => 'required|exists:screws,id',
        ];
    }
}
