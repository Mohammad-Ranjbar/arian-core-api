<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EngineBarcodeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('submit.engine.barcode');
    }


    public function rules(): array
    {
        return [
            'code' => 'required'
        ];
    }
}
