<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionLogResource;
use App\Models\Vehicle;
use App\Repositories\ActionLogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActionLogController extends CrudController
{
    /**
     * @param  ActionLogRepository  $repository
     */
    public function __construct(public readonly  ActionLogRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ActionLogResource::class);
    }

    public function index(Request $request):JsonResponse|AnonymousResourceCollection
    {
        $request->merge(['vin' => $request->route('vehicle')]);
        return parent::index($request);
    }
}
