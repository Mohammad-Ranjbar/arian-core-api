<?php

namespace App\Http\Controllers;

use App\Filters\ProductionLineIdFilter;
use App\Http\Requests\StoreEnterEngineRequest;
use App\Http\Resources\EnterEngineResource;
use App\Models\EnterEngine;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;

class EnterEngineController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('enter-engines.index');
        $data = Pipeline::send(EnterEngine::query())
            ->through([
                ProductionLineIdFilter::class
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

    public function store(StoreEnterEngineRequest $request): EnterEngineResource|JsonResponse
    {
        try {
            $data = EnterEngine::query()->create($request->validated());
            $this->sendPrinter($data);
            return EnterEngineResource::make($data);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function sendPrinter($data)
    {

    }

    public function destroy(EnterEngine $enterEngine): JsonResponse
    {
        try {
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
