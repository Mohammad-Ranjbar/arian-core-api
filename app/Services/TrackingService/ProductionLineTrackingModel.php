<?php

namespace App\Services\TrackingService;

use App\Models\ProductionLine;

class ProductionLineTrackingModel extends BaseTrackingModel
{
    const MODEL_TYPE = 'App\Models\ProductionLine';

    public function enter($data)
    {
        $productionLine = ProductionLine::find($data['model_id']);
        $tracking = $this->getLatestRecord(SalonTrackingModel::MODEL_TYPE, $productionLine->salon_id, $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return false;
        }
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return $this->prepareData($data);
        }
        return false;
    }

    public function exit($data)
    {
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return false;
        }
        return $this->prepareData($data);
    }

    public function prepareData($data)
    {
        $data['model_type'] = self::MODEL_TYPE;
        return $data;
    }
}