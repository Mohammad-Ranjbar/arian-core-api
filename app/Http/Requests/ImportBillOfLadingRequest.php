<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBillOfLadingRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('import.bill-of-lading');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'file'                    => 'required|exists:files,uuid',
            'location_source_id'      => 'required|exists:locations,id',
            'location_destination_id' => 'required|exists:locations,id',
            'type'                    => 'required|in:vehicles,bodies,containers'
        ];
    }
}
