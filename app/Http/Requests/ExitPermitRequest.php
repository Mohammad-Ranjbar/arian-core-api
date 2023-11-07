<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ExitPermitRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'exit_by' => 'nullable|unique:transit_activities,exit_by|exists:transit_activities,id',
            'user_id' => 'nullable',
            'status' => 'nullable',
        ];
    }
    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
            'status'  => 'exit-permit'
        ]);
    }
}
