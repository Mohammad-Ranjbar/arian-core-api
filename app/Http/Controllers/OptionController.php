<?php

namespace App\Http\Controllers;

use App\Repositories\PermissionRepository;
use App\Repositories\ProductionLineRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SalonRepository;
use App\Repositories\StationRepository;
use App\Repositories\WorkGroupCenterRepository;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * @var RoleRepository
     */
    private RoleRepository $roleRepository;
    /**
     * @var PermissionRepository
     */
    private PermissionRepository      $permissionRepository;
    private SalonRepository           $salonRepository;
    private ProductionLineRepository  $productionLineRepository;
    private WorkGroupCenterRepository $workGroupCenterRepository;
    private StationRepository         $stationRepository;

    /**
     * @param  RoleRepository  $roleRepository
     * @param  PermissionRepository  $permissionRepository
     */
    public function __construct(
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        SalonRepository $salonRepository,
        ProductionLineRepository $productionLineRepository,
        WorkGroupCenterRepository $workGroupCenterRepository,
        StationRepository $stationRepository
    ) {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
        $this->salonRepository = $salonRepository;
        $this->productionLineRepository = $productionLineRepository;
        $this->workGroupCenterRepository = $workGroupCenterRepository;
        $this->stationRepository = $stationRepository;
    }

    /**
     * @return array
     */
    public function permissions(): array
    {
        return $this->permissionRepository->getOptions();
    }

    /**
     * @return array
     */
    public function roles(): array
    {
        return $this->roleRepository->getOptions();
    }

    /**
     * @return array
     */
    public function salons(): array
    {
        return $this->salonRepository->getOptions();
    }

    /**
     * @return array
     */
    public function productionLines(): array
    {
        return $this->productionLineRepository->getOptions();
    }

    /**
     * @return array
     */
    public function workGroupCenters(): array
    {
        return $this->workGroupCenterRepository->getOptions();
    }

    /**
     * @return array
     */
    public function stations(): array
    {
        return $this->stationRepository->getOptions();
    }
}
