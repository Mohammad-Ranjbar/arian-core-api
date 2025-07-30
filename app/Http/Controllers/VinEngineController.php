<?php

namespace App\Http\Controllers;

use App\Http\Resources\VinEngineResource;
use App\Repositories\VinEngineRepository;
use Illuminate\Validation\Rule;

class VinEngineController extends CrudController
{

    public function __construct(public readonly VinEngineRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(VinEngineResource::class);
    }

    public function customStoreRules(): array
    {
        return [
            'vin' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vin_engines')->where(function ($query) {
                    return $query->where('engine_no', request()->get('engine_no'));
                }),
            ],
            'engine_no' => 'required|string|max:255',
            'production_order_number' => 'nullable|string|max:255',
        ];
    }

    public function customUpdateRules(): array
    {
        return [
            'vin' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vin_engines')->where(function ($query) {
                    return $query->where('engine_no', request()->get('engine_no'));
                })->ignore(request()->route('vin_engine')),
            ],
            'engine_no' => 'required|string|max:255',
            'production_order_number' => 'nullable|string|max:255',
        ];
    }
}
