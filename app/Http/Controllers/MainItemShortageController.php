<?php

namespace App\Http\Controllers;

use App\Http\Resources\MainItemShortageResource;
use App\Models\MainItemShortage;
use App\Repositories\MainItemShortageRepository;

class MainItemShortageController extends CrudController
{

    public function __construct(public readonly MainItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(MainItemShortageResource::class);
    }

    /**
     * Freeze a draft. From here on, a related item change forks a new draft
     * instead of touching this record.
     */
    public function activate(MainItemShortage $mainItemShortage)
    {
        if (!$mainItemShortage->isDraft()) {
            return $this->responseError('این نسخه قبلا فعال شده است.');
        }

        $mainItemShortage->update(['is_active' => true]);

        return MainItemShortageResource::make($mainItemShortage->load('relatedItems'));
    }
}
