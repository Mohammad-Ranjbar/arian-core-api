<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle.update-grade-status');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'grade' => 'required|string|in:1,2',
            'item_type' => 'required|in:0,1,2',
            'grade_description' => 'nullable',
        ];
    }
}
