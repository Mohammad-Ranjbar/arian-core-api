<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBillOfLadingRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('import.bill-of-lading');
    }


    public function rules(): array
    {
        return [
            //
        ];
    }
}
