<?php

namespace App\Http\Controllers;

use App\Http\Resources\ColorMapResource;
use App\Repositories\ColorMapRepository;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ColorMapController extends CrudController
{

    public function __construct(public readonly ColorMapRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ColorMapResource::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function customStoreRules(): array
    {
        return [
            'code' => ['required','string','max:255',Rule::unique('color_maps', 'code')
                ->where('title', request()->get('location_source_id'))]
        ];
    }
}
