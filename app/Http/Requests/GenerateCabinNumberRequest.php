<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCabinNumberRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('generate.cabin-numbers');
    }


    public function rules(): array
    {
        return [
            'body_option_chr' => 'required|exists:body_options,code'
        ];
    }
}
