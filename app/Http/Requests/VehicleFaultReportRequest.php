<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleFaultReportRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicles.faults.' . $this->route()->parameter('type') . '.report');
    }



    public function rules(): array
    {
        return [
            //
        ];
    }
}
