<?php

namespace App\Http\Controllers;

use App\Filters\SynchronousProductionLineIdFilter;
use App\Filters\SynchronousTitleFilter;
use App\Http\Requests\UpdateCrossReferenceRequest;
use App\Http\Resources\SynchronousResource;
use App\Models\Synchronous;
use App\Models\SynchronousItem;
use App\Services\Erp\ErpCrossReference;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;

class SynchronousController extends Controller
{

    public function __construct(private readonly ErpCrossReference $erpCrossReference)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('index.synchronous');

        $data = Pipeline::send(Synchronous::query()->with('synchronousType')->latest())
            ->through([
                SynchronousProductionLineIdFilter::class,
                SynchronousTitleFilter::class
            ])
            ->thenReturn()->paginate(request()->per_page ?? 15);


        return SynchronousResource::collection($data);
    }

    public function closeSynchronous(Synchronous $synchronous): SynchronousResource
    {

        $synchronous->update([
            'capacity' => 0
        ]);

        return new SynchronousResource($synchronous);
    }

    public function printSynchronous(Synchronous $synchronous): SynchronousResource
    {

        return new SynchronousResource($synchronous);
    }

    public function updateCrossReference(UpdateCrossReferenceRequest $request, SynchronousItem $synchronousItem): JsonResponse|bool
    {
        $validatedData = $request->validated();

        $reference = $synchronousItem->erpProductItem->reference;

        $data = $this->erpCrossReference->get($reference);

        if (!in_array($validatedData['cross_reference'], $data)) {
            return response()->json([
                'message' => 'کراس رفرنس وارد شده نامعتبر است'
            ], 422);
        }
        return $synchronousItem->update([
            'cross_reference' => $validatedData['cross_reference']
        ]);
    }
}
