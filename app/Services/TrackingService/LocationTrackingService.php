<?php

namespace App\Services\TrackingService;

use App\Models\Location;
use App\Models\Station;

class LocationTrackingService extends BaseTrackingService
{
    const MODEL_TYPE = 'App\Models\Location';


    /**
     * @param $data
     * @return array|false
     */
    public function enter($data): array|false
    {
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
     * @return array|false
     */
    public function exit($data): array|false
    {
        return $this->prepareData($data);
    }
}
