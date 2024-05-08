<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\VehicleIncident;

class VehicleIncidentRepository extends BaseRepository
{
    /**
     * @param  VehicleIncident  $model
     */
    public function __construct(VehicleIncident $model)
    {
        $this->model = $model;

    }

    public function getReportData($type, $data)
    {
        $dateField = ($data['date_type'] == 'check') ? 'created_at' : 'checked_at';

        $query = VehicleIncident::query();
        if(!empty($data['from'])) {
            $query = $query->where($dateField, '>=', $data['from']);
        }
        if(!empty($data['to'])) {
            $query = $query->where($dateField, '<=', $data['to']);
        }
        if(!empty($data['make_code'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data){
                $query->whereIn('make_code', $data['make_code']);
                if(!empty($data['car_model'])) {
                    $query->whereIn('car_model_id', $data['car_model']);
                }
            });

        }

        return $query->where('type', $type)->get();


    }

}
