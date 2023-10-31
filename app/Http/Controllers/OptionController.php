<?php

namespace App\Http\Controllers;

use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;

class OptionController extends Controller
{
    /**
     * @var RoleRepository
     */
    private RoleRepository       $roleRepository;
    /**
     * @var PermissionRepository
     */
    private PermissionRepository $permissionRepository;

    /**
     * @param  RoleRepository  $roleRepository
     * @param  PermissionRepository  $permissionRepository
     */
    public function __construct(RoleRepository $roleRepository, PermissionRepository $permissionRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
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
}
