<?php

namespace App\Services\TrackingService;

use App\Jobs\SendTrackingToErpJob;
use App\Models\ProductionLine;

class ProductionLineTrackingService extends BaseTrackingService
{

    /**
     *
     */
    const MODEL_TYPE = 'App\Models\ProductionLine';

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

        return $this->prepareData($data);

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
     */
    public function sendToErp($record): void
    {
        $params = [
            'vINL'         => $record->vin,
            'wCGroup'      => '',
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
