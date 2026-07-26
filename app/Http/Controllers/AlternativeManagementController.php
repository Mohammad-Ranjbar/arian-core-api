<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackingResource;
use App\Models\Tracking;
use App\Services\Erp\ErpGetBOMFromProductionOrderNumber;
use App\Services\Erp\ErpGetBOMItemFromProductionBomNo;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AlternativeManagementController extends Controller
{

    public function __construct(readonly ErpGetBOMFromProductionOrderNumber $erpGetBOMFromProductionOrderNumber,readonly ErpGetBOMItemFromProductionBomNo $erpGetBOMItemFromProductionBomNo,)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function enterLines()
    {
        $lines = Tracking::query()->where('type','enter')->paginate(request()->get('per_page', 10));

        return TrackingResource::collection($lines);
    }

    public function getBomItems(string $productionOrderNumber)
    {

          $bom =  $this->erpGetBOMFromProductionOrderNumber->get($productionOrderNumber);

          $items = $this->erpGetBOMItemFromProductionBomNo->get($bom['production_bom_no']);
          dd($items);

    }
}
