<?php

namespace App\Http\Requests;

use App\Repositories\BlockItemRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
        $createValidateRules = app()->make(BlockItemRepository::class);

        return $createValidateRules->getCreateRules();
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'vin'  => $this->route()->parameter('vehicle')->vin,
            'blocked_by' => Auth::id(),
        ]);
    }

}
