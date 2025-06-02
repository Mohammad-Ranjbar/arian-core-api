<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingExitDoorReportExcelRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-excel-tracking-exit-door');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'type' => 'in:enter,exit',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'car_model_id' => 'required|exists:car_models,id',
            'make_code_id' => 'required|exists:make_codes,id',
            'locations_ids' => 'required|array',
            'locations_ids.*' => 'exists:locations,id',
        ];
    }


}
