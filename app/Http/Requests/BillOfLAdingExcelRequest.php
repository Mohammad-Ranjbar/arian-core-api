<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillOfLAdingExcelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-excel-bill-of-ladings');
    }


    public function rules(): array
    {
        return [
            'type' => 'nullable|in:vehicle,container,body',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
        ];
    }
}
