<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
use App\Models\User;
use App\Repositories\AlternativeRepository;
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

        $alternatives = Alternative::query()->whereIn('item_no', $alternativeGroupItems)->get();

        return AlternativeResource::collection($alternatives);

    }
}
