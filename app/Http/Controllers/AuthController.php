<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\ChangePasswordUserRequest;
use App\Http\Requests\RecoverPasswordRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    /**
     * @param  SmsService  $smsService
     */
    public function __construct(private readonly SmsService $smsService)
    {

    }


    /**
     * @param  AuthRequest  $request
     * @return JsonResponse
     */
    public function login(AuthRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $user = User::query()->where('email', $validatedData['username'])
                ->orWhere('mobile', $validatedData['username'])
                ->first();

            if (!isset($user)) {
                return $this->responseError('user not found', 404);
            }
            if (!Hash::check($validatedData['password'], $user->password)) {
                return $this->responseError('Unauthorized', 401);
            }
            return $this->makeToken($user);
        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), $exception->getCode());
        }

    }

    /**
     * @param $user
     * @return JsonResponse
     */
    private function makeToken($user): JsonResponse
    {
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'token_type'  => 'Bearer',
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function init(Request $request): JsonResponse
    {
        return response()->json([
            'user'        => new UserResource($request->user()),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->all()
        ]);
    }

    /**
     * @param  ChangePasswordUserRequest  $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            if (!Hash::check($validatedData['oldPassword'], $request->user()->password)) {
                return $this->responseError('The previous password was entered incorrectly');
            }
            $request->user()->update(['password' => bcrypt($validatedData['newPassword'])]);
            return response()->json([
                'message' => 'The password was changed successfully'
            ]);
        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), $exception->getCode());
        }
    }

    /*
     *
     */
    /**
     * @param  RecoverPasswordRequest  $request
     * @return JsonResponse
     */
    public function recoverPassword(RecoverPasswordRequest $request): JsonResponse
    {
        if (Cache::get('AUTH:USER:2FA'.$request->validated('mobile'))) {
            return $this->responseError('To many attempt!');
        }
        $code = 11111;
        if (app()->environment() == 'production') {
            $code = rand(10000, 99999);
        }
        Cache::put('AUTH:USER:2FA'.$request->validated('mobile'), $code, 130);
        $this->smsService->send($request->validated('mobile'), '2fa', compact('code'));
        return response()->json([
            'message' => 'Send!',
        ]);
    }


    /**
     * @param  VerifyCodeRequest  $request
     * @return JsonResponse
     */
    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        $code = Cache::get('AUTH:USER:2FA'.$request->validated('mobile'));
        if (!$code) {
            return response()->json(
                ['message' => 'invalid code'], 422);
        }
        if ($code != $request->validated('code')) {
            return $this->responseError('invalid code');
        }
        $user = User::query()->where('mobile', $request->validated('mobile'))->first();
        Cache::delete('AUTH:USER:2FA'.$request->validated('mobile'));
        return $this->makeToken($user);
    }

    /**
     * @param  Request  $request
     * @return UserResource
     */
    public function checkUserName(Request $request): UserResource
    {
        $validatedData = $request->validate(['username' => 'required']);
        $user = User::query()->where('email', $validatedData['username'])
            ->orWhere('mobile', $validatedData['username'])
            ->firstOrFail();
        return new UserResource($user);
    }
}
