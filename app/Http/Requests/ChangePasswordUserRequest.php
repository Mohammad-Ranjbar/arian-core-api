<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordUserRequest extends FormRequest
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
            'newPassword' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[!@#$%]).+$/'
            ],
            'oldPassword' => 'required|string',
        ];
    }
}
