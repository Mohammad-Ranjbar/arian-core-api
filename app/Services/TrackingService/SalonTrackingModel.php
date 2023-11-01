<?php

namespace App\Services\TrackingService;

class SalonTrackingModel extends BaseTrackingModel
{
    const MODEL_TYPE = 'App\Models\Salon';

    public function enter($data)
    {
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return $this->prepareData($data);
        }
        return false;
    }
    public function prepareData($data)
    {
        $data['model_type'] = self::MODEL_TYPE;
        return $data;
    }

    public function exit($data)
    {
        $tracking = $this->getLatestRecord(self::MODEL_TYPE, $data['model_id'], $data['vin']);
        if (!$tracking or $tracking->type === 'exit') {
            return false;
        }
        return $this->prepareData($data);
    }
}