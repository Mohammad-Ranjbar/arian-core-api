<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'user_id' => 'nullable',
            'value' => 'required|numeric',
            'screw_id' => 'required|exists:screws,id',


            'torque_measuring_equipment_id' => 'required|exists:torque_measuring_equipments,id',
            'torque_meter_id' => 'required|exists:torque_meters,id',
            'torque_test_group_id' => 'required|exists:torque_test_groups,id',
            'description' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
