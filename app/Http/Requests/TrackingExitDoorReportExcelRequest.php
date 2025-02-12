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
        ];
    }


}
