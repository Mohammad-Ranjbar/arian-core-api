<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportExcelToqueTestRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('export-vehicle-torque-test');
    }


    public function rules(): array
    {
        return [
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'car_model_id' => 'required|exists:car_models,id',
        ];
    }
}
