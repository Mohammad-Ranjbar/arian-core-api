<?php

namespace App\Services\TrackingService;

use App\Events\ErpJobEvent;
use App\Jobs\PrintSynchronousJob;
use App\Models\Alternative;
use App\Models\AlternativeItem;
use App\Models\ErpJob;
use App\Models\ErpProductItem;
use App\Models\NavColor;
use App\Models\ProductionLine;
use App\Models\ShortageItem;
use App\Models\Synchronous;
use App\Models\SynchronousItem;
use App\Models\SynchronousType;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetBOMFromProductionOrderNumber;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Octane\Exceptions\DdException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionLineTrackingService extends BaseTrackingService
{
    const string MODEL_TYPE = 'App\Models\ProductionLine';

    /**
     * @param $data
     * @return false|string
     */
    public function enter($data): array|false
    {

        $productionLine = ProductionLine::query()->find($data['model_id']);
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $productionLine->getAttribute('id'),
            $data['vin']);

        if ($tracking and $tracking->getAttribute('type') === 'enter') {
            return false;
        }
        $this->submitSynchronous($data['vin'], $productionLine->getAttribute('id'));
        $this->submitAlternative($data['vin']);
        return $this->prepareData($data);

    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DdException
     * @throws Exception
     */
    public function submitAlternative(string $vin)
    {

        $vehicle = Vehicle::query()->where('vin', $vin)->first();
        $productionOrderNumber = $vehicle->getAttribute('production_order_number');

        $items = resolve(ErpGetBOMFromProductionOrderNumber::class)->get($productionOrderNumber);;

        foreach ($items as $item) {

            $alternativeItem = AlternativeItem::query()->where('item_no',$item['item_no'])->first();
            if (empty($alternativeItem)) {
                continue;
//                throw new Exception('Alternative Item already not Exists. => '.$item['item_no']);
            }

            $alternative = Alternative::query()
                ->where('item_no',$item['item_no'])
                ->where('status','create')
                ->first();
            if ($item['has_color']){
                $colorMap =  $this->colorMap($item['variant_code'],$vehicle);
            }
            if ( $alternative ) {
                $alternative->increment('counter');
            }else{
              $alternative =  Alternative::query()->create([
                    'alternative_item_id' => $alternativeItem->getAttribute('id'),
                    'production_order_number' => $productionOrderNumber,
                    'item_no' => $item['item_no'],
                    'counter' => 1,
                    'status' => 'create',
                ]);
            }
            $alternative->variants()->create([
                'variant_code' => $colorMap,
            ]);
            if ($alternative->getAttribute('counter') == $alternative->alternativeItem->getAttribute('packing_qty')) {
                $alternative->update([
                    'status' => 'send'
                ]);
            }
        }
    }


    public function colorMap(string $variantCode,$vehicle)
    {
      return  NavColor::query()->where('color',$variantCode)
                ->where('car_model_id',$vehicle->getAttribute('car_model_id'))
                ->first()?->getAttribute('code') ?? '';
    }

    public function submitSynchronous(string $vin, $productionLineId): void
    {

        $synchronousTypes = SynchronousType::query()->where('production_line_id', $productionLineId)->get();
        $vehicle = Vehicle::query()->find($vin);

        foreach ($synchronousTypes as $synchronousType) {
            $synchronous = Synchronous::query()->where('capacity', '>', 0)
                ->where('synchronous_type_id', $synchronousType->id)->first();
            $item = $synchronousType->items()->where('car_model_id',
                $vehicle->getAttribute('car_model_id'))->where('is_active', true)->first();

            if (!$item) {
                continue;
            }
            if (!$synchronous) {
                $counter = Synchronous::query()
                        ->where('synchronous_type_id', $synchronousType->id)->max('counter') + 1;

                $synchronous = Synchronous::query()->create([
                    'synchronous_type_id' => $synchronousType->id,
                    'counter'             => $counter,
                    'capacity'            => $synchronousType->qty,
                ]);

            }

            SynchronousItem::query()->create([
                'synchronous_id'      => $synchronous->getAttribute('id'),
                'vin'                 => $vin,
                'erp_product_item_id' => $item->getAttribute('erp_product_item_id'),
                'cross_reference'     => $item->getAttribute('cross_reference'),
            ]);
            $synchronous->decrement('capacity');

            dispatch(new PrintSynchronousJob($synchronous->getAttribute('id')));

        }


    }

    /**
     * @param $data
     * @return array
     */
    public function prepareData($data): array
    {
        $data['model_type'] = self::MODEL_TYPE;
        return $data;
    }

    /**
     * @param $data
     * @return string|false
     */
    public function exit($data): array|false
    {
        $productionLine = ProductionLine::query()->find($data['model_id']);
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $productionLine->getAttribute('id'),
            $data['vin']);

        if ($tracking and $tracking->getAttribute('type') === 'exit') {
            return false;
        }
        return $this->prepareData($data);
    }

    public function sendToErp($record): void
    {
        $type = $record->getAttribute('model')->item_type;

        $params = [
            'vINL' => $record->vin,
            'wCGroup' => '',
            'withTracking' => 'false',
            'withOutput' => 'true',
            'itemTypeP' => $type
        ];

        if ($record->type == 'exit') {
            $params['withOutput'] = 'true';
        }


        $method = 'PostProduction_OnlyBIN';
        $shortages = ShortageItem::query()->where('vin', $record->vin)->whereNull('shortage_item_id')
            ->where('status', 'pending')->with('erpProductItem')->get();
        if ($count = $shortages->count()) {
            $method = 'PostProduction_OnlyBIN_Shortage';
            $params['lackItemNo'] = $shortages->implode('erpProductItem.reference', ',');
            $params['lackRoutingNo'] = $shortages->implode('routing_link_code', ',');
            $params['numberFractionalItems'] = $count;
            $params['itemTypeP'] = $type;
        }

        $data = [
            'user_id'    => Auth::id(),
            'section'    => 'production-line-tracking-'.$record->type,
            'model_id'   => $record->id,
            'model_type' => Tracking::class,
            'method'     => $method,
            'url'        => config('erp.post_production'),
            'parameters' => $params,
            'queue'      => ErpJob::QUEUE_UNIQUE
        ];

        $erpJob = ErpJob::query()->where([
            'model_id'   => $record->id,
            'model_type' => Tracking::class,
        ])->first();
        if (empty($erpJob)) {
            $erpJob = ErpJob::query()->create($data);
        }

        ErpJobEvent::dispatch($erpJob);
    }
}
