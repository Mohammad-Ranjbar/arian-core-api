<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionTestResource;
use App\Repositories\ProductionTestRepository;
use Illuminate\Http\JsonResponse;

class ProductionTestController extends CrudController
{

    /**
     * @param ProductionTestRepository $repository
     */
    public function __construct(public readonly ProductionTestRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionTestResource::class);
    }

    /**
     * @param $id
     * @return JsonResponse|bool
     */
    public function destroy($id): JsonResponse|bool
    {
        if ($this->repository->model->getAttribute('productionTestResults')->count() > 0){
            return $this->responseError('cant delete this production test before there is results for this');
        }
        return $this->repository->destroy($id);
    }
}
