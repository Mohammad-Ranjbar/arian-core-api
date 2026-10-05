<?php

namespace App\Http\Controllers;

use App\Filters\VinExactFilter;
use App\Http\Requests\StoreRegisterShortageRequest;
use App\Http\Requests\UpdateRegisterShortageRequest;
use App\Http\Requests\VinReferenceRequest;
use App\Http\Resources\RegisterShortageResource;
use App\Models\MainItemShortage;
use App\Models\RegisterShortage;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetShortageBOM;
use App\Services\Erp\ErpGetWorkCenterFromRoutingLink;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class RegisterShortageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RegisterShortage::class, 'register_shortage');
    }

    public function shortagesVehicle(string $vin)
    {
        try {
            $vehicle = Vehicle::query()->where('vin', $vin)->firstOrFail();
            $shortages = RegisterShortage::query()->where('vin', $vehicle->getAttribute('vin'))->latest()->get();

            return RegisterShortageResource::collection($shortages);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function setShortagesVehicle(VinReferenceRequest $request, string $vin)
    {
        try {
            $vehicle = Vehicle::query()
                ->where('vin', $vin)
                ->firstOrFail();

            $destinationVin = $request->validated('vin');
            $destinationVehicle = Vehicle::query()->findOrFail($destinationVin);
            if ($vehicle->getAttribute('car_model_id') != $destinationVehicle->getAttribute('car_model_id')) {
                return $this->responseError('دو شاسی از یک نوع نیستند.');
            }

            if ($destinationVin == $vehicle->getAttribute('vin')) {
                return $this->responseError('Vin is different from source vin');
            }

            DB::transaction(function () use ($vin, $destinationVehicle) {
                $sourceShortages = RegisterShortage::query()
                    ->where('vin', $vin)
                    ->whereNull('register_shortage_id')
                    ->where(function ($query) {
                        $query->where('type', RegisterShortage::TYPE_SINGLE)
                            ->orWhere(function ($query) {
                                $query->where('type', RegisterShortage::TYPE_MAIN)
                                    ->whereIn('main_item_shortage_id', MainItemShortage::query()
                                        ->select('id')
                                        ->where('is_active', true));
                            });
                    })
                    ->withOnly('children')
                    ->get();

                $destinationAttributes = [
                    'vin' => $destinationVehicle->getAttribute('vin'),
                    'car_model_id' => $destinationVehicle->getAttribute('car_model_id'),
                    'make_code_id' => $destinationVehicle->getAttribute('make_code_id'),
                    'user_id' => Auth::id(),
                ];

                foreach ($sourceShortages as $shortage) {
                    $copiedShortage = RegisterShortage::query()->create([
                        ...$shortage->only($shortage->getFillable()),
                        ...$destinationAttributes,
                        'register_shortage_id' => null,
                    ]);

                    foreach ($shortage->children->where('vin', $vin) as $child) {
                        RegisterShortage::query()->create([
                            ...$child->only($child->getFillable()),
                            ...$destinationAttributes,
                            'register_shortage_id' => $copiedShortage->getKey(),
                        ]);
                    }
                }
            });

            $shortages = RegisterShortage::query()
                ->where('vin', $destinationVin)
                ->latest()
                ->get();

            return RegisterShortageResource::collection($shortages);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->responseError('شماره قطعه اصلی یا یکی از قطعات وابسته قبلاً برای خودروی مقصد ثبت شده است؛ هیچ کسری کپی نشد.');
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $data = Pipeline::send(RegisterShortage::query()
            ->whereNull('register_shortage_id')
            ->with(['vehicle', 'carModel', 'makeCode', 'user', 'children'])
            ->latest())
            ->through([
                VinExactFilter::class,
            ])
            ->thenReturn()
            ->paginate($perPage);

        return RegisterShortageResource::collection($data);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function store(StoreRegisterShortageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $vehicle = Vehicle::query()->findOrFail($validated['vin']);
        $mainItem = null;
        if ($validated['type'] === RegisterShortage::TYPE_MAIN) {
            $mainItem = MainItemShortage::query()
                ->whereKey($validated['main_item_shortage_id'])
                ->where('car_model_id', $vehicle->getAttribute('car_model_id'))
                ->where('item_no', $validated['item_no'])
                ->where('is_active', true)
                ->with('relatedItems')
                ->first();

            if ($mainItem === null) {
                throw ValidationException::withMessages([
                    'main_item_shortage_id' => 'آیتم اصلی انتخاب‌شده باید فعال و متعلق به مدل خودرو باشد و شماره قطعه آن با شماره قطعه ثبت‌شده یکسان باشد.',
                ]);
            }

            $existingItemNo = RegisterShortage::query()
                ->where('vin', $validated['vin'])
                ->whereIn('item_no', $mainItem->relatedItems->pluck('item_no'))
                ->value('item_no');

            if ($existingItemNo !== null) {
                throw ValidationException::withMessages([
                    'item_no' => "قطعه وابسته با شماره {$existingItemNo} قبلاً برای این خودرو ثبت شده است.",
                ]);
            }
        }
        $workCenter = null;
        $validated['is_certified'] = false;
        if ($validated['type'] == 'single') {
            $items = resolve(ErpGetShortageBOM::class)
                ->get($vehicle->carModel->reference,$validated['item_no']);
            if (collect($items)->count() > 0) {
                $validated['is_certified'] = true;
            }
        }

        if ($validated['type'] == 'main') {
            $validated['is_certified'] = true;
            $items = resolve(ErpGetShortageBOM::class)
                ->get($vehicle->carModel->reference,$validated['item_no']);
            dd(collect($items)->first);
            $workCenter = collect($items)
                ->first()?->work_center ?? null;
        }

        try {
            $registerShortage = DB::transaction(function () use ($validated, $vehicle, $mainItem, $workCenter) {
                $attributes = [
                    ...$validated,
                    'car_model_id' => $vehicle->getAttribute('car_model_id'),
                    'make_code_id' => $vehicle->getAttribute('make_code_id'),
                    'status' => RegisterShortage::STATUS_HAS_SHORTAGE,
                    'user_id' => Auth::id(),
                ];
                $registerShortage = RegisterShortage::query()->create([
                    ...$attributes,
                    'work_center' => $workCenter,
                ]);

                if ($mainItem !== null) {
                    foreach ($mainItem->relatedItems as $relatedItem) {
                        RegisterShortage::query()->create([
                            ...$attributes,
                            'item_no' => $relatedItem->getAttribute('item_no'),
                            'register_shortage_id' => $registerShortage->getKey(),
                            'item_description' => $relatedItem->getAttribute('description'),
                        ]);
                    }
                }

                return $registerShortage->load('children');
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (!str_contains($exception->getMessage(), 'register_shortages_vin_item_no_unique')
                && !str_contains($exception->getMessage(), 'register_shortages.vin, register_shortages.item_no')) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'item_no' => 'شماره قطعه اصلی یا یکی از قطعات وابسته برای این خودرو تکراری است.',
            ]);
        }

        return RegisterShortageResource::make($registerShortage)->response()->setStatusCode(201);
    }

    public function show(RegisterShortage $registerShortage): RegisterShortageResource
    {
        return RegisterShortageResource::make(
            $registerShortage->load(['vehicle', 'carModel', 'makeCode', 'user'])
        );
    }

    public function update(
        UpdateRegisterShortageRequest $request,
        RegisterShortage              $registerShortage
    ): RegisterShortageResource
    {
        $validated = $request->validated();

        if (array_key_exists('vin', $validated)) {
            $vehicle = Vehicle::query()->findOrFail($validated['vin']);
            $validated['car_model_id'] = $vehicle->getAttribute('car_model_id');
            $validated['make_code_id'] = $vehicle->getAttribute('make_code_id');
        }

        if (($validated['type'] ?? null) === RegisterShortage::TYPE_SINGLE) {
            $validated['work_center'] = null;
        }

        $registerShortage->update($validated);

        return RegisterShortageResource::make(
            $registerShortage->refresh()->load(['vehicle', 'carModel', 'makeCode', 'user'])
        );
    }

    public function destroy(RegisterShortage $registerShortage): JsonResponse
    {
        $registerShortage->delete();

        return response()->json([
            'message' => 'Register shortage deleted successfully.',
        ]);
    }
}
