<?php

namespace App\Http\Controllers;

use App\Exports\BlockItemReportExport;
use App\Http\Resources\BlockItemResource;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use App\Services\Erp\ErpWsdlClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class BlockItemController extends Controller
{
    /**
     * @param  BlockItemRepository  $repository
     */
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
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse|BlockItemResource
    {
        try {
            $this->authorize('block-items.store');
            $validatedData = $request->validate($this->repository->getCreateRules());
            $validatedData['blocked_by'] = Auth::id();
            $validatedData['vin'] = $vehicle->vin;

            ErpWsdlClient::run(config('erp.block_unblock'), 'BlockingVehicle', ['barcodeInfoP', $vehicle->vin]);
            return new BlockItemResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
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
            $this->authorize('block-items.unblock');
            $data = [
                'unblocked_by' => Auth::id(), 'unblocked_at' => Carbon::now(),
            ];
            $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();
            $item->update($data);

            $exists = $vehicle->blockItems()->whereNull('unblocked_at')->exists();
            if (!$exists) {
                ErpWsdlClient::run(config('erp.block_unblock'), 'UnblockingVehicle', ['barcodeInfoP', $vehicle->vin]);
            }

            return new BlockItemResource($item);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function report()
    {
        $data = $this->repository->get();

        return Excel::download(new BlockItemReportExport($data), 'blockItemExport-'.time().'.xlsx',
            \Maatwebsite\Excel\Excel::XLSX);
    }
}
