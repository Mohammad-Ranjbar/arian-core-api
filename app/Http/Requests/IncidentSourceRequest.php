<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentSourceRequest extends FormRequest
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
            'model_type' => 'bail|required|in:salons,production_lines,stations,work_group_centers,locations',
            'model_id'   => 'required_if:model_type,!=,""|exists:'.$this->input('model_type').',id',
        ];
    }
}
