<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendAlternativeRequest;
use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
use App\Repositories\AlternativeRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Support\Facades\Auth;

class AlternativeController extends CrudController
{

    public function __construct(public readonly AlternativeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeResource::class);
    }

    public function listAlternatives()
    {
        $alternativeGroupUser = AlternativeCategory::query()
            ->where('user_id', Auth::id())
            ->first();

        $alternativeGroupItems = $alternativeGroupUser->items()->select('item_no')->get()->toArray();

        $alternatives = Alternative::query()->whereIn('item_no', $alternativeGroupItems)->paginate(25);

        return AlternativeResource::collection($alternatives);
    }

    public function sendAlternatives(SendAlternativeRequest $request)
    {
        try {
            $response = ErpWsdlClient::run(
                $erpJob->getAttribute('url'),
                $erpJob->getAttribute('method'),
                $erpJob->getAttribute('parameters')
            );
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
