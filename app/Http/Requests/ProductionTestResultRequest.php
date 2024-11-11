<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionTestResultRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-excel-test-results');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'from' => 'nullable',
            'to' => 'nullable',
            'user_id' => 'nullable|exists:users,id',
            'production_test_id' => 'nullable|exists:production_tests,id',
            'vin' => 'nullable|string|exists:vehicles,vin',
        ];
    }
}
