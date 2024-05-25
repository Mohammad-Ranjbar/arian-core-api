<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TrackingReportExcelRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-excel-tracking');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'model_type' => 'in:salons,production_lines,stations',
            'model_id' => 'nullable',
            'type' => 'in:enter,exit',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
        ];
    }


}
