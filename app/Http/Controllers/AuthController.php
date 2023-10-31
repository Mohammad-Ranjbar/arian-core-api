<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\ChangePasswordUserRequest;
use App\Http\Requests\RecoverPasswordRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\UserResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @param  AuthRequest  $request
     * @return JsonResponse
     */
    public function login(AuthRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            if (!Auth::attempt($validatedData)) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $user = $request->user();
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'accessToken' => $token, 'token_type' => 'Bearer',
            ]);
        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), $exception->getCode());
        }

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
    public function checkUser(Request $request): JsonResponse
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
                return response()->json([
                    'message' => 'The previous password was entered incorrectly'
                ], 422);
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
     * TODO implement recover password
     */
    /**
     * @param  RecoverPasswordRequest  $request
     * @return array
     */
    public function recoverPassword(RecoverPasswordRequest $request): array
    {
        return $request->validated();
    }

    /**
     * @param  VerifyCodeRequest  $request
     * @return array
     */
    public function verifyCode(VerifyCodeRequest $request):array
    {
        return $request->validated();
    }
}
