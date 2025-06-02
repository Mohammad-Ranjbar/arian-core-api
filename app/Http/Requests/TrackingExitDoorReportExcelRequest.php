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
            'item_type' => 'required|in:0,1,2',
            'location_ids' => 'required|array',
            'location_ids.*' => 'integer|exists:locations,id',
            'car_model_id' => 'integer|exists:car_models,id',
            'make_code_id' => 'integer|exists:make_codes,id',
        ];
    }


}
