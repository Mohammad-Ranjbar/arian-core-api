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
            'from' => 'required|date|date_format:Y-m-d',
            'to' => 'required|date|date_format:Y-m-d',
            'make_code' => 'required|array',
            'make_code.*' => 'required|exists:make_codes,id',
            'car_model' => 'required|array',
            'car_model.*' => 'required|exists:make_codes,id',
            'type' => 'required|in:qc,evaluation',
            'user_id' => 'nullable',
            'method' => 'nullable',
            'intensity' => 'nullable',
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
