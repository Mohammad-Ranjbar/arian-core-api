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
            'location_id' => 'exists:locations,id',
            'type' => 'string|in:exit,enter',
            'exit_type' => 'integer|in:1,2',
            'from' => 'nullable',
            'to' => 'nullable',
        ];
    }
}
