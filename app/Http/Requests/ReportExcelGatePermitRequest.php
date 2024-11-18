<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportExcelGatePermitRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'nullable|exists:locations,id',
            'types' => 'nullable|array',
            'types.*' => 'in:exit,enter',
            'exit_types' => 'array',
            'exit_types.*' => 'integer|in:1,2',
            'from' => 'nullable',
            'to' => 'nullable',
        ];
    }
}
