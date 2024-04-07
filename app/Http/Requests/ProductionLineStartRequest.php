<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'start_at' => 'nullable|date_format:"Y-m-d H:i:s"',
            'notify'   => 'required|boolean'
        ];
    }
}
