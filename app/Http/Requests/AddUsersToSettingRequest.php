<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddUsersToSettingRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('setting.assign.user');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id'
        ];
    }
}
