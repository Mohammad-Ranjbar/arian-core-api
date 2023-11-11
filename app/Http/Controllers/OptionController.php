<?php

namespace App\Http\Controllers;

use App\Repositories\ElementRepository;
use App\Repositories\IncidentRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProductionLineRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SalonRepository;
use App\Repositories\StationRepository;
use App\Repositories\TransitCompanyRepository;
use App\Repositories\TransitDestinationRepository;
use App\Repositories\TransitDriverRepository;
use App\Repositories\TransitSourceRepository;
use App\Repositories\WorkGroupCenterRepository;
use Illuminate\Contracts\Container\BindingResolutionException;

class OptionController extends Controller
{
    /**
     * @param $resource
     * @return mixed
     * @throws BindingResolutionException
     */
    public function __invoke($resource):mixed
    {
        $repo = $this->match($resource);
        if (!$repo) {
            abort(404);
        }
        return app()->make($repo)->getOptions();
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
            'transit-drivers' => TransitDriverRepository::class,
            'transit-companies' => TransitCompanyRepository::class,
            'transit-sources' => TransitSourceRepository::class,
            'transit-destinations' => TransitDestinationRepository::class,
            'incidents' => IncidentRepository::class,
            'elements' => ElementRepository::class,
            default => false,
        };
    }


}
