<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionLineStartRequest extends FormRequest
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
            'start_at' => ['nullable','date_format:"Y-m-d H:i:s"','after:stop_at'],
            'notify'   => ['required','boolean']
        ];
    }
}
