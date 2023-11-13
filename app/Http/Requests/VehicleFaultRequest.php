<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VehicleFaultRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicles.faults.'.$this->route()->parameter('type').'.submit');
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'        => [
                'required', 'in:qc,evaluation',
                Rule::unique('vehicle_incidents')->where(function ($query) {
                    return $query->where('incident_id', $this->input('incident_id'))
                        ->where('element_id', $this->input('element_id'))
                        ->where('type', $this->input('type'))
                        ->where('vin', $this->input('vin'));
                })
            ],
            'intensity'   => 'required|string',
            'method'      => 'required|string',
            'description' => 'nullable',
            'user_id'     => 'nullable',
            'vin'         => 'nullable',
            'checked_at'  => 'nullable|date|date_format:Y-m-d H:i:s',
            'element_id'  => 'required|exists:elements,id',
            'incident_id' => 'required|exists:incidents,id',
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation():void
    {
        $this->merge([
            'type'    => $this->route()->parameter('type'),
            'vin'     => $this->route()->parameter('vehicle')->vin,
            'user_id' => Auth::id(),
        ]);
    }
}
