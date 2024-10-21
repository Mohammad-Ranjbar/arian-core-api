<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FaultReportExcelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('vehicles.faults.' . $this->route()->parameter('type') . '.report_excel');
    }

    public function rules(): array
    {
        return [
            'date_type' => 'required|in:create,check',
            'from' => 'required|date',
            'to' => 'required|date',
            'make_code' => 'required|array',
            'make_code.*' => 'required|exists:make_codes,id',
            'car_model' => 'nullable|array',
            'car_model.*' => 'nullable|exists:car_models,id',
            'type' => 'required|in:qc,evaluation',
            'user_id' => 'nullable',
            'method' => 'nullable',
            'intensities' => 'nullable',
            'incident_id' => 'array',
            'incident_id.*' => 'exists:incidents,id',
            'element_id' => 'array',
            'element_id.*' => 'exists:elements,id',
            'custom_location_type' => 'nullable|in:location,station',
            'custom_location_id' => 'nullable',
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route()->parameter('type'),
            'user_id' => Auth::id()
        ]);
    }
}
