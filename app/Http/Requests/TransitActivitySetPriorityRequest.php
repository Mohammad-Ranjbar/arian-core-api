<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitActivitySetPriorityRequest extends FormRequest
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
            'priority' => 'required|integer|in:1,0'
        ];
    }
}
