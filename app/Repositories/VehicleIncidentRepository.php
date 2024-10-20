<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\VehicleIncident;

class VehicleIncidentRepository extends BaseRepository
{
    /**
     * @param VehicleIncident $model
     */
    public function __construct(VehicleIncident $model)
    {
        $this->model = $model;

    }

    public function getReportData($type, $data)
    {
        $dateField = ($data['date_type'] == 'check') ? 'checked_at' : 'created_at';

        $query = VehicleIncident::query();
        if (!empty($data['from'])) {
            $query = $query->where($dateField, '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where($dateField, '<=', $data['to']);
        }
        if (!empty($data['intensities'])) {
            $query = $query->whereIn('intensity',  $data['intensities']);
        }
        if (!empty($data['method'])) {
            $query = $query->where('method',  $data['method']);
        }
        if (!empty($data['custom_location_id'])) {
            $query = $query->where('custom_location_id',  $data['custom_location_id']);
        }
        if (!empty($data['custom_location_type'])) {
            $query = $query->where('custom_location_type',  $data['custom_location_type']);
        }
        if (!empty($data['incident_id'])) {
            $query = $query->whereIn('incident_id',  $data['incident_id']);
        }
        if (!empty($data['element_id'])) {
            $query = $query->whereIn('element_id',  $data['element_id']);
        }
        if (!empty($data['make_code']) || !empty($data['car_model'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                if (!empty($data['make_code'])) {
                    $query->whereIn('make_code_id', $data['make_code']);
                }
                if (!empty($data['car_model'])) {
                    $query->whereIn('car_model_id', $data['car_model']);
                }
            });
        }

        return $query->where('type', $type)->get();


    }

}
