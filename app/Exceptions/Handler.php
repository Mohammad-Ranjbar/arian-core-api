<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{

    protected $dontFlash = ['current_password', 'password', 'password_confirmation',];

    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|RedirectResponse|Response
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->json(['message' => '404 ! The requested resource could not be found.'], 404);
        }

        return parent::render($request, $e);
    }

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
