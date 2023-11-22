<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockItemResource;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class BlockItemController extends Controller
{
    public function __construct(public readonly BlockItemRepository $repository)
    {
    }

    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        Request $request,
        Vehicle $vehicle
    ): JsonResponse|AnonymousResourceCollection {
        $this->authorize('block-items.index');
        $perPage = $request->get('per_page', 15);
        return BlockItemResource::collection($vehicle->blockItems()->paginate($perPage));
    }

    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|BlockItemResource
     * @throws AuthorizationException
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse|BlockItemResource
    {
        $this->authorize('block-items.store');
        $validatedData = $request->validate($this->repository->getCreateRules());
        $validatedData['blocked_by'] = Auth::id();
        $validatedData['vin'] = $vehicle->vin;
        return new BlockItemResource($this->repository->store($validatedData));
    }

    /**
     * @param  Vehicle  $vehicle
     * @param $id
     * @return BlockItemResource|JsonResponse
     * @throws AuthorizationException
     */
    public function unblock(Vehicle $vehicle, $id): BlockItemResource|JsonResponse
    {
        $this->authorize('block-items.unblock');
        $data = [
            'unblocked_by' => Auth::id(), 'unblocked_at' => Carbon::now(),
        ];
        $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();
        $item->update($data);
        return new BlockItemResource($item);
    }
}
