<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;


class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @param  string  $message
     * @param int $code
     * @return JsonResponse
     */
    public function responseError(string $message,int $code = 422): JsonResponse
    {
        return response()->json([
            'message' => $message
        ], $code);
    }
}
