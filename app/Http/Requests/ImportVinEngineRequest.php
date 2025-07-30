<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportVinEngineRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('import.vin-engines');
    }


    public function rules(): array
    {
        return [
            'file' => 'required|exists:files,uuid',
        ];
    }
}
