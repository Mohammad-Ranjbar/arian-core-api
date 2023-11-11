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
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly SalonRepository $salonRepository,
        private readonly ProductionLineRepository $productionLineRepository,
        private readonly WorkGroupCenterRepository $workGroupCenterRepository,
        private readonly StationRepository $stationRepository,
        private readonly MakeCodeRepository $makeCodeRepository,
        private readonly TransitSourceRepository $transitSourceRepository,
        private readonly TransitDestinationRepository $transitDestinationRepository,
        private readonly TransitDriverRepository $transitDriverRepository,
        private readonly TransitCompanyRepository $transitCompanyRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly ElementRepository $elementRepository,
    ) {
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
