<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecoverPasswordRequest extends FormRequest
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
            'mobile' => 'required|digits:11|starts_with:09|exists:users,mobile'
        ];
    }
}
