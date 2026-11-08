<?php

namespace App\Services\TrackingService;

use App\Jobs\PrintSynchronousJob;
use App\Models\Alternative;
use App\Models\AlternativeItem;
use App\Models\NavColor;
use App\Models\ProductionLine;
use App\Models\RegisterShortage;
use App\Models\SpecificShortageItem;
use App\Models\Synchronous;
use App\Models\SynchronousItem;
use App\Models\SynchronousType;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetBOMFromProductionOrderNumber;
use App\Services\Erp\RestApi\ProductionPostingBINsService;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ProductionLineTrackingService extends BaseTrackingService
{
    const string MODEL_TYPE = 'App\Models\ProductionLine';

    public function pbs(): bool
    {
        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function enter($data): array|false
    {

        $productionLine = ProductionLine::query()->find($data['model_id']);
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $productionLine->getAttribute('id'), $data['vin']);

        if ($tracking and $tracking->getAttribute('type') === 'enter') {
            return false;
        }
        $this->submitSynchronous($data['vin'], $productionLine->getAttribute('id'));
        $this->submitAlternative($data['vin']);
        return $this->prepareData($data);

    }

    public function submitSynchronous(string $vin, $productionLineId): void
    {

        $synchronousTypes = SynchronousType::query()->where('production_line_id', $productionLineId)->get();
        $vehicle = Vehicle::query()->find($vin);

        foreach ($synchronousTypes as $synchronousType) {
            $synchronous = Synchronous::query()->where('capacity', '>', 0)->where('synchronous_type_id',
                $synchronousType->id)->first();
            $item = $synchronousType->items()->where('car_model_id',
                $vehicle->getAttribute('car_model_id'))->where('is_active', true)->first();

            if (!$item) {
                continue;
            }
            if (!$synchronous) {
                $counter = Synchronous::query()->where('synchronous_type_id', $synchronousType->id)->max('counter') + 1;

                $synchronous = Synchronous::query()->create([
                    'synchronous_type_id' => $synchronousType->id, 'counter' => $counter,
                    'capacity' => $synchronousType->qty,
                ]);

            }

            SynchronousItem::query()->create([
                'synchronous_id' => $synchronous->getAttribute('id'), 'vin' => $vin,
                'erp_product_item_id' => $item->getAttribute('erp_product_item_id'),
                'cross_reference' => $item->getAttribute('cross_reference'),
            ]);
            $synchronous->decrement('capacity');

            dispatch(new PrintSynchronousJob($synchronous->getAttribute('id')));

        }


    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function submitAlternative(string $vin): void
    {
        $vehicle = Vehicle::query()
            ->where('vin', $vin)
            ->firstOrFail();

        $productionOrderNumber = $vehicle->getAttribute('production_order_number');

        $items = resolve(ErpGetBOMFromProductionOrderNumber::class)
            ->get($productionOrderNumber);

        foreach ($items as $item) {
            $alternativeItem = AlternativeItem::query()
                ->where('item_no', $item['item_no'])
                ->first();

            if (!$alternativeItem) {
                continue;
            }

            $colorMap = $item['has_color']
                ? $this->colorMap($item['variant_code'], $vehicle)
                : null;

            // packing_qty is now decimal
            $packingQty = (float)$alternativeItem->getAttribute('packing_qty');

            // Quantity may also contain decimal values
            $remaining = (float)$item['quantity'];

            while ($remaining > 0) {
                $alternative = $this->makeAlternative(
                    $item,
                    $alternativeItem,
                    $productionOrderNumber
                );

                // Calculate the actual used capacity from variants
                $used = (float)$alternative
                    ->variants()
                    ->sum('quantity');

                $applicable = $packingQty - $used;

                /*
                 * The alternative is already full but its status
                 * has not been changed yet.
                 */
                if ($applicable <= 0) {
                    $alternative->update([
                        'status' => 'send',
                    ]);

                    continue;
                }

                /*
                 * Add only the amount that can fit into
                 * the current alternative.
                 */
                $addVar = min($applicable, $remaining);

                $variant = $alternative
                    ->variants()
                    ->firstOrCreate(
                        [
                            'variant_code' => $colorMap,
                        ],
                        [
                            'quantity' => 0,
                        ]
                    );

                $variant->increment('quantity', $addVar);

                $alternative->increment('counter', $addVar);

                $remaining -= $addVar;

                /*
                 * If the packing capacity has been reached,
                 * close this alternative.
                 */
                if (($used + $addVar) >= $packingQty) {
                    $alternative->update([
                        'status' => 'send',
                    ]);
                }
            }
        }
    }

    public function colorMap(string $variantCode, $vehicle)
    {
        return NavColor::query()->where('color', $variantCode)->where('car_model_id',
            $vehicle->getAttribute('car_model_id'))->first()?->getAttribute('code') ?? '';
    }

    private function makeAlternative(
        $item,
        $alternativeItem,
        $productionOrderNumber
    )
    {
        $alternative = Alternative::query()
            ->where('item_no', $item['item_no'])
            ->where('status', 'create')
            ->first();

        if ($alternative) {
            return $alternative;
        }

        return Alternative::query()->create([
            'alternative_item_id' => $alternativeItem->getAttribute('id'),
            'production_order_number' => $productionOrderNumber,
            'item_no' => $item['item_no'],
            'counter' => 0,
            'status' => 'create',
        ]);
    }

    public function prepareData($data): array
    {
        $data['model_type'] = self::MODEL_TYPE;
        return $data;
    }

    /**
     * @throws Exception
     */
    public function exit($data): array|false
    {
        $productionLine = ProductionLine::query()->find($data['model_id']);
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $productionLine->getAttribute('id'), $data['vin']);

        if ($tracking and $tracking->getAttribute('type') === 'exit') {
            return false;
        }
//        $this->checkShortages($data['vin']);
        return $this->prepareData($data);
    }

    /**
     * @throws Exception
     */
    public function checkShortages(string $vin): void
    {
        $shortages = RegisterShortage::query()
            ->where('vin', $vin)
            ->where('type', RegisterShortage::TYPE_MAIN)
            ->whereHas('mainItemShortage', function ($mainItemShortage) {
                $mainItemShortage->where('location_code', 'offline');
            })
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
                throw new Exception('این شماره Vin دارای قطعه کسری میباشد و امکان خروج ندارد.' . ' ::' . $shortage->getAttribute('item_no'));
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function sendToErp($record): array
    {
        return resolve(ProductionPostingBINsService::class)->send(
            $record->vin, '', 'false', 'true', $record->model->item_type, model: $record
        )->unique();
    }

    // پیاده‌سازی قبلی SOAP:
    // public function sendToErp($record): void
    // {
    //     $type = $record->getAttribute('model')->item_type;
    //
    //     $params = [
    //         'vINL' => $record->vin, 'wCGroup' => '',
    //         'withTracking' => 'false',
    //         'withOutput' => 'true',
    //         'itemTypeP' => $type
    //     ];
    //
    //     if ($record->type == 'exit') {
    //         $params['withOutput'] = 'true';
    //     }
    //
    //
    //     $method = 'PostProduction_OnlyBIN';
    //
    //     $data = [
    //         'user_id' => Auth::id(), 'section' => 'production-line-tracking-' . $record->type,
    //         'model_id' => $record->id, 'model_type' => Tracking::class, 'method' => $method,
    //         'url' => config('erp.post_production'), 'parameters' => $params, 'queue' => ErpJob::QUEUE_UNIQUE
    //     ];
    //
    //     $erpJob = ErpJob::query()->where(['model_id' => $record->id, 'model_type' => Tracking::class,])->first();
    //     if (empty($erpJob)) {
    //         $erpJob = ErpJob::query()->create($data);
    //     }
    //
    //     ErpJobEvent::dispatch($erpJob);
    // }
}
