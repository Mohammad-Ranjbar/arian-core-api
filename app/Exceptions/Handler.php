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

    /**
     * @var string[]
     */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation',];

    /**
     * @param $request
     * @param  Throwable  $e
     * @return \Illuminate\Http\Response|JsonResponse|RedirectResponse|Response
     * @throws Throwable
     */
    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|RedirectResponse|Response
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->json(['message' => '404 ! The requested resource could not be found.'], 404);
        }

        return response()->json([
            'type' => get_class($exception),
            'message' => $exception->getMessage()
        ]);
//        return parent::render($request, $e);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }
}
