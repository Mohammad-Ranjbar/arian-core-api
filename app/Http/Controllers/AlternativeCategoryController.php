<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignItemToAlternativeCategoryRequest;
use App\Http\Resources\AlternativeCategoryResource;
use App\Models\AlternativeCategory;
use App\Repositories\AlternativeCategoryRepository;
use Exception;
use Illuminate\Http\JsonResponse;

class AlternativeCategoryController extends CrudController
{
    public function __construct(public readonly AlternativeCategoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeCategoryResource::class);
    }

    public function assignItem(AlternativeCategory $alternativeCategory, AssignItemToAlternativeCategoryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $alternativeCategory->items()->create($validatedData);
            $alternativeCategory->refresh();
            return AlternativeCategoryResource::make($alternativeCategory);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
