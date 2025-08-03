<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportIncidentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'file' => 'required|exists:files,uuid',
            'make_code_id' => 'required|exists:make_codes,id',
        ];
    }
}
