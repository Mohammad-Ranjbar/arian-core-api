<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VehicleRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [

        ], 'update' => [

        ]
    ];

    /**
     * @param Vehicle $model
     */
    public function __construct(Vehicle $model)
    {
        $this->model = $model;
    }

    public function make($data,string  $parameter ='vin')
    {
        $po = $data['production_order_number'];
        $vehicle = $this->model->newQuery()
            ->where($parameter, $data[$parameter])
            ->where('production_order_number',$po)->first();
        if ($vehicle) {
            $vehicle->update($data);
        }else{
            $this->model->newQuery()->where('production_order_number',$po)->update([
                'production_order_number' => null
            ]);
            $vehicle = $this->model->newQuery()
                ->where($parameter, $data[$parameter])->first();
            if ($vehicle) {
                $vehicle->update($data);
            }else {
                $vehicle = $this->model->newQuery()->create($data);
            }
        }

        return $vehicle;
    }


}
