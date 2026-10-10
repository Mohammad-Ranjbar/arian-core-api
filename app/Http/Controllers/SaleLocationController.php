<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToSaleLocationRequest;
use App\Http\Requests\SetSaleLocationRequest;
use App\Http\Resources\SaleLocationResource;
use App\Http\Resources\TrackingSaleLocationResource;
use App\Http\Resources\UserShortResource;
use App\Models\RegisterShortage;
use App\Models\SaleLocation;
use App\Models\SpecificShortageItem;
use App\Models\Tracking;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\SaleLocationRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class SaleLocationController extends CrudController
{

    /**
     * @param  SaleLocationRepository  $repository
     */
    public function __construct(public readonly SaleLocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleLocationResource::class);
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @param  AddUsersToSaleLocationRequest  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignUser(
        SaleLocation $saleLocation,
        AddUsersToSaleLocationRequest $request
    ): JsonResponse {
        $this->authorize('saleLocation.assign.user');
        $validatedData = $request->validated();
        try {
            if ($saleLocation->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انبار فروش انتخاب شده موجود است', 400);
            }
            $saleLocation->users()->attach($validatedData);
            return response()->json(new SaleLocationResource($saleLocation));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(SaleLocation $saleLocation): AnonymousResourceCollection
    {
        $this->authorize('saleLocation.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($saleLocation->users()->paginate($perPage));
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(SaleLocation $saleLocation, User $user): JsonResponse
    {
        $this->authorize('saleLocation.remove.user');
        try {
            $saleLocation->users()->detach($user->getAttribute('id'));
            return response()->json(new SaleLocationResource($saleLocation));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  Vehicle  $vehicle
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAvailableSaleLocations(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('get-available-sale-locations');
        $saleLocations = SaleLocation::query()->whereHas('makeCode', function ($makeCode) use ($vehicle) {
            $makeCode->where('id', $vehicle->makeCode->id);
        })->whereHas('users', function ($q) {
            $q->where('users.id', Auth::id());
        })->select(['id', 'title'])->get();

        return new JsonResponse(
            data: $saleLocations,
            status: Response::HTTP_OK
        );
    }

    /**
     * @param  Vehicle  $vehicle
     * @param  SetSaleLocationRequest  $request
     * @return Model|JsonResponse
     */
    public function setSaleLocation(
        Vehicle $vehicle,
        SetSaleLocationRequest $request
    ): Model|JsonResponse {
        $validatedData = $request->validated();
        try {
            $saleLocation = SaleLocation::query()->where('id', $validatedData['sale_location_id'])
                ->whereHas('makeCode', function ($makeCode) use ($vehicle) {
                    $makeCode->where('id', $vehicle->makeCode->id);
                })->whereHas('users', function ($q) {
                    $q->where('users.id', Auth::id());
                })->first();
            if (!$saleLocation) {
                return $this->responseError('cant find sale location for this situation', 400);
            }
            $this->checkShortages($vehicle->getAttribute('vin'));
            $response = $this->transfer($vehicle->getAttribute('vin'), $saleLocation->getAttribute('code'));
            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }
            return $vehicle->tracking()->create([
                'user_id'    => Auth::id(),
                'type'       => 'enter',
                'model_type' => SaleLocation::class,
                'model_id'   => $validatedData['sale_location_id']
            ]);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  string  $vin
     * @param  string  $saleLocationCode
     * @return array
     */
    private function transfer(string $vin, string $saleLocationCode): array
    {
        return ErpWsdlClient::run(config('erp.transfer_vehicle'), 'TransferVehicleCommercialProducts_Zone',
            ['vINP' => $vin, 'toLocationTypeP' => $saleLocationCode]);

    }


    public function listSaleLocations(): AnonymousResourceCollection
    {
        $data = request()->all();

        $data['model_type'] = 'sale_locations';
        $data['paginate'] = true;
        $data = Tracking::getReportData($data);

        return TrackingSaleLocationResource::collection($data);

    }

    /**
     * @throws Exception
     */
    public function checkShortages(string $vin): void
    {
        $shortages = RegisterShortage::query()
            ->where('vin', $vin)
            ->whereHas('mainItemShortage',function ($mainItemShortage){
                $mainItemShortage->where('location_code','PDI');
            })
            ->orWhere('type', RegisterShortage::TYPE_SINGLE)
            ->get();
        $carModelId = Vehicle::query()->where('vin', $vin)->first()->car_model_id;
        foreach ($shortages as $shortage) {
            $checkSpecificShortageItem = SpecificShortageItem::query()
                ->where('is_active', 1)
                ->where('car_model_id', $carModelId)
                ->where('item_no', $shortage->item_no)->exists();
            if ($checkSpecificShortageItem) {
                continue;
            }
            if ($shortage->getAttribute('status') == RegisterShortage::STATUS_HAS_SHORTAGE) {
                throw new Exception('این شماره Vin دارای قطعه کسری میباشد و امکان انتقال به انبار قروش ندارد.');
            }
        }
    }
}
