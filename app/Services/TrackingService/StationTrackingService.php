<?php

namespace App\Services\TrackingService;

use App\Jobs\SendTrackingToErpJob;
use App\Models\Station;

class StationTrackingService extends BaseTrackingService
{
    const MODEL_TYPE = 'App\Models\Station';


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
            'vINL'         => $record->vin,
            'wCGroup'      => $record->model->code,
            'withTracking' => 'false',
            'withOutput'   => 'false',
        ];

        if ($record->type == 'exit') {
            $params['withOutput'] = 'true';
        }

        dispatch(new SendTrackingToErpJob($record->id, config('erp.post_production'), 'PostProduction_OnlyBIN',
            $params));
    }
}
