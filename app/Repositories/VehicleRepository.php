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
        $vehicle = $this->model->newQuery()->where($parameter, $data[$parameter])->first();
        if (empty($vehicle)) {
            if ($this->model->newQuery()->where('production_order_number', $data['production_order_number'])->exists()) {
                return new JsonResponse(
                    data: ['message' => ' production order number for this VIN is exist'],
                    status: Response::HTTP_BAD_REQUEST
                );

            }
            $vehicle = $this->model->newQuery()->create($data);
        }else{
            $vehicle->update($data);
        }

        return $vehicle;
    }


}
