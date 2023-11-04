<?php

namespace App\Services\TrackingService;

use App\Models\ProductionLine;

class ProductionLineTrackingService extends BaseTrackingService
{

    const MODEL_TYPE = 'App\Models\ProductionLine';

    /**
     * @param $data
     * @return false|string
     */
    public function enter($data): array|false
    {

        $productionLine = ProductionLine::query()->find($data['model_id']);
        $tracking = $this->getLatestRecord(SalonTrackingService::MODEL_TYPE, $productionLine->salon_id, $data['vin']);

        if (!$tracking or $tracking->type === 'exit') {
            return false;
        }
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return $this->prepareData($data);
        }
        return false;
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
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return false;
        }
        return $this->prepareData($data);
    }
}