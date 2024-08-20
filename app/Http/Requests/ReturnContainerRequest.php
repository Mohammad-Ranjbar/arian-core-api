<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnContainerRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('return-container');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'return_container_at' => 'required|date_format:Y-m-d',
        ];
    }
}
