<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BlockItemStoreRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('block-items.store');
    }


    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function rules(): array
    {
        return [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
            'blocked_by'      => 'nullable',
            'vin'             => [
                'nullable',
                Rule::unique('block_items')->where(function ($query) {
                    return $query->where('block_reason_id', $this->get('block_reason_id'))->whereNull('unblocked_at');
                }),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'vin.unique' => 'این خودرو با همین دلیل بلوک است'
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'vin'        => $this->route()->parameter('vehicle')->vin,
            'blocked_by' => Auth::id(),
        ]);
    }

}
