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

class OptionController extends Controller
{
    /**
     * @var RoleRepository
     */
    private RoleRepository $roleRepository;
    /**
     * @var PermissionRepository
     */
    private PermissionRepository $permissionRepository;
    /**
     * @var SalonRepository
     */
    private SalonRepository $salonRepository;
    /**
     * @var ProductionLineRepository
     */
    private ProductionLineRepository $productionLineRepository;
    /**
     * @var WorkGroupCenterRepository
     */
    private WorkGroupCenterRepository $workGroupCenterRepository;
    /**
     * @var StationRepository
     */
    private StationRepository $stationRepository;
    /**
     * @var MakeCodeRepository
     */
    private MakeCodeRepository $makeCodeRepository;
    /**
     * @var TransitSourceRepository
     */
    private TransitSourceRepository $transitSourceRepository;
    /**
     * @var TransitDestinationRepository
     */
    private TransitDestinationRepository $transitDestinationRepository;
    /**
     * @var TransitDriverRepository
     */
    private TransitDriverRepository $transitDriverRepository;
    /**
     * @var TransitCompanyRepository
     */
    private TransitCompanyRepository $transitCompanyRepository;
    /**
     * @var IncidentRepository
     */
    private IncidentRepository $incidentRepository;
    private ElementRepository  $elementRepository;


    /**
     * @param  RoleRepository  $roleRepository
     * @param  PermissionRepository  $permissionRepository
     * @param  SalonRepository  $salonRepository
     * @param  ProductionLineRepository  $productionLineRepository
     * @param  WorkGroupCenterRepository  $workGroupCenterRepository
     * @param  StationRepository  $stationRepository
     * @param  MakeCodeRepository  $makeCodeRepository
     * @param  TransitSourceRepository  $transitSourceRepository
     * @param  TransitDestinationRepository  $transitDestinationRepository
     * @param  TransitDriverRepository  $transitDriverRepository
     * @param  TransitCompanyRepository  $transitCompanyRepository
     */
    public function __construct(
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        SalonRepository $salonRepository,
        ProductionLineRepository $productionLineRepository,
        WorkGroupCenterRepository $workGroupCenterRepository,
        StationRepository $stationRepository,
        MakeCodeRepository $makeCodeRepository,
        TransitSourceRepository $transitSourceRepository,
        TransitDestinationRepository $transitDestinationRepository,
        TransitDriverRepository $transitDriverRepository,
        TransitCompanyRepository $transitCompanyRepository,
        IncidentRepository $incidentRepository,
        ElementRepository $elementRepository,
    ) {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
        $this->salonRepository = $salonRepository;
        $this->productionLineRepository = $productionLineRepository;
        $this->workGroupCenterRepository = $workGroupCenterRepository;
        $this->stationRepository = $stationRepository;
        $this->makeCodeRepository = $makeCodeRepository;
        $this->transitSourceRepository = $transitSourceRepository;
        $this->transitDestinationRepository = $transitDestinationRepository;
        $this->transitDriverRepository = $transitDriverRepository;
        $this->transitCompanyRepository = $transitCompanyRepository;
        $this->incidentRepository = $incidentRepository;
        $this->elementRepository = $elementRepository;
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

    /**
     * @return array
     */
    public function makeCodes(): array
    {
        return $this->makeCodeRepository->getOptions();
    }

    /**
     * @return array
     */
    public function transitDriver(): array
    {
        return $this->transitDriverRepository->getOptions();
    }

    /**
     * @return array
     */
    public function transitCompany(): array
    {
        return $this->transitCompanyRepository->getOptions();
    }

    /**
     * @return array
     */
    public function transitSource(): array
    {
        return $this->transitSourceRepository->getOptions();
    }

    /**
     * @return array
     */
    public function transitDestination(): array
    {
        return $this->transitDestinationRepository->getOptions();
    }

    /**
     * @return array
     */
    public function incidents(): array
    {
        return $this->incidentRepository->getOptions();
    }

    /**
     * @return array
     */
    public function elements(): array
    {
        return $this->elementRepository->getOptions();
    }


}
