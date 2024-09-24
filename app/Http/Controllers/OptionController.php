<?php

namespace App\Http\Controllers;

use App\Repositories\BlockReasonRepository;
use App\Repositories\CarModelRepository;
use App\Repositories\CarRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\DriverRepository;
use App\Repositories\ElementRepository;
use App\Repositories\ErpProductItemRepository;
use App\Repositories\IncidentRepository;
use App\Repositories\LocationRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProductionLineRepository;
use App\Repositories\ProductionTestRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SaleLocationRepository;
use App\Repositories\SalonRepository;
use App\Repositories\StationRepository;
use App\Repositories\StopFactRepository;
use App\Repositories\StopReasonRepository;
use App\Repositories\StopTypeRepository;
use App\Repositories\TransitCompanyRepository;
use App\Repositories\WorkGroupCenterRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;

class OptionController extends Controller
{
    /**
     * @param $resource
     * @return JsonResponse|array
     * @throws BindingResolutionException
     */
    public function __invoke($resource): JsonResponse|array
    {
        $repository = $this->match($resource);
        if (!$repository) {
            return $this->responseError('repository not found', 404);
        }
        return app()->make($repository)->getOptions();
    }

    /**
     * @param $resource
     * @return bool|string
     */
    public function match($resource): bool|string
    {
        return match ($resource) {
            'permissions' => PermissionRepository::class,
            'roles' => RoleRepository::class,
            'salons' => SalonRepository::class,
            'production-lines' => ProductionLineRepository::class,
            'work-group-centers' => WorkGroupCenterRepository::class,
            'stations' => StationRepository::class,
            'make-codes' => MakeCodeRepository::class,
            'cars' => CarRepository::class,
            'transit-companies' => TransitCompanyRepository::class,
            'locations' => LocationRepository::class,
            'incidents' => IncidentRepository::class,
            'elements' => ElementRepository::class,
            'containers' => ContainerRepository::class,
            'stop-facts' => StopFactRepository::class,
            'stop-types' => StopTypeRepository::class,
            'stop-reasons' => StopReasonRepository::class,
            'block-reasons' => BlockReasonRepository::class,
            'car-models' => CarModelRepository::class,
            'drivers' => DriverRepository::class,
            'sale-locations' => SaleLocationRepository::class,
            'product-items' => ErpProductItemRepository::class,
            'production-tests' => ProductionTestRepository::class,
            default => false,
        };
    }


}
