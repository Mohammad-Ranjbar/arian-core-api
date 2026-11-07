<?php

namespace App\Http\Controllers;

use App\Filters\EngineNoFilter;
use App\Filters\MakeCodeFilter;
use App\Filters\ProductionLineIdFilter;
use App\Http\Requests\StoreEnterEngineRequest;
use App\Http\Resources\EnterEngineResource;
use App\Jobs\PrintEngineLableJob;
use App\Models\EnterEngine;
use App\Services\EngineNumberLabelPrinter;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;
use RuntimeException;

class EnterEngineController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('enter-engines.index');
        $data = Pipeline::send(EnterEngine::query()->latest('counter')
            ->with('makeCode')
            ->whereIn('make_code_id',Auth::user()->makeCodes()->pluck('make_code_id')->toArray()))
            ->through([
                ProductionLineIdFilter::class,
                EngineNoFilter::class,
                MakeCodeFilter::class,
            ])
            ->thenReturn()
            ->paginate(request()->input('per_page', 10));

        return EnterEngineResource::collection($data);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(EnterEngine $enterEngine): EnterEngineResource
    {
        $this->authorize('enter-engines.show');
        return EnterEngineResource::make($enterEngine);
    }

    public function printLabel(EnterEngine $enterEngine, EngineNumberLabelPrinter $printer): JsonResponse
    {
        $this->authorize('enter-engines.print');

        try {
            $printer->print($enterEngine->engine_no);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'ارسال لیبل به چاپگر انجام نشد؛ تنظیمات، ارتباط و وضعیت چاپگر را بررسی کنید.',
            ], 502);
        }

        return response()->json([
            'message' => 'داده‌های لیبل به چاپگر ارسال شد.',
            'data' => [
                'id' => $enterEngine->id,
                'engine_no' => $enterEngine->engine_no,
                'printer_ip' => config('printers.engine_label.ip'),
                'label_size_mm' => [100, 20],
            ],
        ]);
    }

    public function store(StoreEnterEngineRequest $request): EnterEngineResource|JsonResponse
    {
        try {
            $data = EnterEngine::query()->create($request->validated());
            return EnterEngineResource::make($data);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function sendPrinter(EnterEngine $enterEngine): JsonResponse
    {
        $this->authorize('enter-engines.store');

        PrintEngineLableJob::dispatch($enterEngine->getAttribute('engine_no'), '192.168.147.193')->afterCommit();

        return response()->json([
            'message' => 'درخواست چاپ برچسب شماره موتور در صف قرار گرفت.',
        ], 202);
    }

    public function destroy(EnterEngine $enterEngine): JsonResponse
    {
        try {
            $this->authorize('force-delete-enter-engine');

            $enterEngine->delete();

            return response()->json([
                'message' => 'Engine deleted.'
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }
}
