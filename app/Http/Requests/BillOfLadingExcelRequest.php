<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillOfLadingExcelRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('report-excel-bill-of-ladings');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|in:vehicle,container,body',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
        ];
    }
}
