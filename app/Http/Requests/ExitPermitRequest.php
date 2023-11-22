<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExitPermitRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
      return $this->user()->can('transit-activities-set-exit-permit');
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'exit_by' => 'nullable|unique:transit_activities,exit_by|exists:transit_activities,id',
        ];
    }

}
