<?php

namespace App\Services\TrackingService;

use App\Events\ErpJobEvent;
use App\Jobs\PrintSynchronousJob;
use App\Models\ErpJob;
use App\Models\ProductionLine;
use App\Models\ShortageItem;
use App\Models\Synchronous;
use App\Models\SynchronousItem;
use App\Models\SynchronousType;
use App\Models\Tracking;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Support\Facades\Auth;

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
        return $this->prepareData($data);

    }

    public function submitSynchronous(string $vin, $productionLineId): void
    {
        $synchronousTypes = SynchronousType::query()->where('production_line_id', $productionLineId)->get();

        foreach ($synchronousTypes as $synchronousType) {
            $synchronous = Synchronous::query()->where('capacity', '>', 0)
                ->where('synchronous_type_id', $synchronousType->id)->first();
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
                'synchronous_id' => $synchronous->getAttribute('id'),
                'vin'            => $vin
            ]);
            $synchronous->decrement('capacity');
            dispatch(new PrintSynchronousJob($synchronous->id));
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

    /**
     * @param $record
     * @return void
     * @throws Exception
     */
    public function sendToErp($record): void
    {
        $params = [
            'vINL'         => $record->vin,
            'wCGroup'      => '',
            'withTracking' => 'false',
            'withOutput'   => 'true',//change to true => shaghayegh
        ];

        if ($record->type == 'exit') {
            $params['withOutput'] = 'true';
        }


        $method = 'PostProduction_OnlyBIN';
        $shortages = ShortageItem::query()->where('vin', $record->vin)
            ->where('status', 'pending')->with('erpProductItem')->get();
        if ($count = $shortages->count()) {
            $method = 'PostProduction_OnlyBIN_Shortage';
            $params['lackItemNo'] = $shortages->implode('erpProductItem.reference',',');
            $params['numberFractionalItems'] = $count;
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
