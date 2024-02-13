<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBlockRequest extends FormRequest
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
            'file'            => 'required|exists:files,uuid',
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
        ];
    }
}
