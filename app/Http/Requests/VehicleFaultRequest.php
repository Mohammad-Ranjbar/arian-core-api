<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'intensity'   => 'required|string',
            'method'      => 'required|string',
            'description' => 'nullable',
            'user_id'     => 'nullable',
            'vin'         => 'nullable',
            'checked_at'  => 'nullable|date|date_format:Y-m-d H:i:s',
            'element_id'  => 'required|exists:elements,id',
            'incident_id' => 'required|exists:incidents,id',
            'type'        => 'nullable'
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route()->parameter('type'),
            'vin'  => $this->route()->parameter('vehicle')->vin, 'user_id' => Auth::id(),
        ]);
    }
}
