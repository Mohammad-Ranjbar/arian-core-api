<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
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
            'file' => 'required|exists:files,uuid',
            'makeCodes' => 'nullable|array',
            'makeCodes.*' => 'nullable|exists:make_codes,id',
        ];
    }
}
