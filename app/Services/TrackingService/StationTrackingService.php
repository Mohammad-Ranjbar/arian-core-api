<?php

namespace App\Services\TrackingService;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use App\Models\Station;
use App\Models\Tracking;
use Illuminate\Support\Facades\Auth;

class StationTrackingService extends BaseTrackingService
{
    const string MODEL_TYPE = 'App\Models\Station';


    /**
     * @param $data
     * @return array|false
     */
    public function enter($data): array|false
    {
        $station = Station::query()->find($data['model_id']);
        $tracking = $this
            ->getLatestRecord(
                ProductionLineTrackingService::MODEL_TYPE,
                $station->getAttribute('workGroupCenter')->production_line_id,
                $data['vin']
            );
//        if (!$tracking or $tracking->getAttribute('type') === 'exit') {
//            return false;
//        }
//        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
//        if (!$tracking or $tracking->getAttribute('type') === 'exit') {
        return $this->prepareData($data);
//        }
//        return false;
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
     * @return array|false
     */
    public function exit($data): array|false
    {
        $tracking = $this->getLatestRecord(
            self::MODEL_TYPE,
            $data['model_id'],
            $data['vin']
        );

        if (!$tracking or $tracking->getAttribute('type') === 'exit') {
            return false;
        }
        return $this->prepareData($data);
    }

    /**
     * @param $record
     * @return void
     */
    public function sendToErp($record): void
    {
        $params = [
            'vINL' => $record->vin,
            'wCGroup' => $record->model->code,
            'withTracking' => 'false',
            'withOutput' => 'false',
        ];

        if ($record->type == 'exit') {
            $params['withOutput'] = 'true';
        }

        $data = [
            'user_id' => Auth::id(),
            'section' => 'station-tracking-' . $record->type,
            'model_id' => $record->id,
            'model_type' => Tracking::class,
            'method' => 'PostProduction_OnlyBIN',
            'url' => config('erp.post_production'),
            'parameters' => $params,
            'queue' => ErpJob::QUEUE_UNIQUE
        ];


        $erpJob = ErpJob::query()->where([
            'model_id' => $record->id,
            'model_type' => Tracking::class,
        ])->first();
        if (empty($erpJob)){
            $erpJob = ErpJob::query()->create($data);
        }

        ErpJobEvent::dispatch($erpJob);
    }
}
