<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockItemResource;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class BlockItemController extends Controller
{
    private BlockItemRepository $repository;

    /**
     * @param  BlockItemRepository  $repository
     */
    public function __construct(BlockItemRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(
        Request $request,
        Vehicle $vehicle
    ): AnonymousResourceCollection {
        $perPage = $request->get('per_page', 15);
        return BlockItemResource::collection($vehicle->blockItems()->paginate($perPage));
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        try {
            $validatedData['blocked_by'] = Auth::id();
            $validatedData['vin'] = $vehicle->vin;
            return new BlockItemResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function unblock(Request $request, Vehicle $vehicle, $id): BlockItemResource
    {
        $data = [
            'unblocked_by' => Auth::id(),
            'unblocked_at' => Carbon::now(),
        ];
        $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();
        $item->update($data);
        return new BlockItemResource($item);

    }
}
