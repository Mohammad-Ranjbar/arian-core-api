<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveShortageRequest;
use App\Http\Resources\RegisterShortageResource;
use App\Models\RegisterShortage;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ResolveShortageController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function __invoke(ResolveShortageRequest $request)
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($validated) {
                $shortage = RegisterShortage::query()
                    ->lockForUpdate()
                    ->find($validated['register_shortage_id']);

                if (!$shortage) {
                    return $this->responseError('رکورد کسری پیدا نشد.');
                }

                if (
                    $shortage->resolved_by !== null ||
                    $shortage->resolved_at !== null
                ) {
                    return $this->responseError(
                        'این کسری قبلاً رفع شده است و امکان ثبت مجدد وجود ندارد.'
                    );
                }

                $shortage->update([
                    'resolved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                $shortage->refresh();

                return RegisterShortageResource::make($shortage);
            });
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
