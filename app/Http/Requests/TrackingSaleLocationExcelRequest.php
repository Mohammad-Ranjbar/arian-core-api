<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TrackingSaleLocationExcelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('report-excel-tracking-sale-locations');
    }

    public function rules(): array
    {
        return [
            'model_id' => 'nullable',
            'type' => 'in:enter,exit',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'make_code_id' => 'required|exists:make_codes,id',
            'sale_location_id' => 'required|exists:sale_locations,id',
        ];
    }

}
