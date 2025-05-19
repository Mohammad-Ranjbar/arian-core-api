<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RepeatVehicleTorqueTestRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'vin' => 'required|exists:vehicles,vin',
            'value' => 'required',
            'user_id' => 'nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id()
        ]);
    }
}
