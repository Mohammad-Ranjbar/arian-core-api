<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShortageReportExcelRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-shortages');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'items' => 'array',
            'items.*' => 'nullable|exists:erp_product_items,reference',
            'vins' => 'nullable|array',
            'vins.*' => 'exists:vehicles,vin',
            'make_code_id' => 'nullable|exists:make_codes,id',
            'car_model_id' => 'nullable|exists:car_models,id',

        ];
    }
}
