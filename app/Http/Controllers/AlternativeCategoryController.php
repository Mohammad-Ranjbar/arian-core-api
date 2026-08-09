<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\AlternativeCategoryResource;
use App\Http\Resources\StopFactResource;
use App\Http\Resources\UserShortResource;
use App\Models\AlternativeCategory;
use App\Models\StopFact;
use App\Models\User;
use App\Repositories\AlternativeCategoryRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AlternativeCategoryController extends CrudController
{
    public function __construct(public readonly AlternativeCategoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeCategoryResource::class);
    }


    public function assignUser(AlternativeCategory $alternativeCategory, AddUserToStationsRequest $request): JsonResponse
    {
        $this->authorize('alternative-category.assign.user');
        $validatedData = $request->validated();
        try {
            if ($alternativeCategory->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انتخاب شده موجود است', 400);
            }

            $alternativeCategory->users()->attach($validatedData['user_id']);
            return response()->json(new StopFactResource($alternativeCategory));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(),500);
        }
    }



    public function users(AlternativeCategory $alternativeCategory): AnonymousResourceCollection
    {

        $this->authorize('alternative-category.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($alternativeCategory->users()->paginate($perPage));
    }


    public function removeUser(AlternativeCategory $alternativeCategory, User $user): JsonResponse
    {
        $this->authorize('alternative-category.remove.user');
        try {
            $alternativeCategory->users()->detach($user->getAttribute('id'));

            return response()->json(new AlternativeCategoryResource($alternativeCategory));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }
}
