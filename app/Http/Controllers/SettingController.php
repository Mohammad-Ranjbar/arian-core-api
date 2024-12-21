<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToSettingRequest;
use App\Http\Resources\SettingResource;
use App\Http\Resources\UserShortResource;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SettingController extends CrudController
{

    public function __construct(public readonly SettingRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SettingResource::class);
    }


    public function assignUsers(
        Setting                  $setting,
        AddUsersToSettingRequest $request
    ): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            if ($setting->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انتخاب شده موجود است', 400);
            }
            $setting->users()->attach($validatedData);
            return response()->json(new SettingResource($setting->fresh()));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     */
    public function users(Setting $setting): AnonymousResourceCollection
    {
        $this->authorize('setting.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($setting->users()->paginate($perPage));
    }


    /**
     * @throws AuthorizationException
     */
    public function removeUser(Setting $setting, User $user): JsonResponse
    {
        $this->authorize('setting.remove.user');
        try {
            $setting->users()->detach($user->getAttribute('id'));

            return response()->json(new SettingResource($setting));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
