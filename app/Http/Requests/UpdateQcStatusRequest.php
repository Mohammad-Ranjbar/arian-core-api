<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQcStatusRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('vehicle.update-qc-status');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'qc_status'      => 'required|string',
            'qc_description' => 'nullable',
//            'type' => 'required|string'
        ];
    }
}
