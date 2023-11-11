<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockItemResource;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class BlockItemController extends Controller
{
    /**
     * @var BlockItemRepository
     */
    private BlockItemRepository $repository;

    /**
     * @param  BlockItemRepository  $repository
     */
    public function __construct(BlockItemRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(
        Request $request,
        Vehicle $vehicle
    ): JsonResponse|AnonymousResourceCollection {
        try {
            $this->authorize('block-items-index');
            $perPage = $request->get('per_page', 15);
            return BlockItemResource::collection($vehicle->blockItems()->paginate($perPage));
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }

    }

    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|BlockItemResource
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse|BlockItemResource
    {
        try {
            $this->authorize('block-items-store');
            $validatedData = $request->validate($this->repository->getCreateRules());
            $validatedData['blocked_by'] = Auth::id();
            $validatedData['vin'] = $vehicle->vin;
            return new BlockItemResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  Vehicle  $vehicle
     * @param $id
     * @return BlockItemResource|JsonResponse
     */
    public function unblock(Vehicle $vehicle, $id): BlockItemResource|JsonResponse
    {
        try {
            $this->authorize('block-items-unblock');
            $data = [
                'unblocked_by' => Auth::id(), 'unblocked_at' => Carbon::now(),
            ];
            $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();
            $item->update($data);
            return new BlockItemResource($item);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }


    }
}
