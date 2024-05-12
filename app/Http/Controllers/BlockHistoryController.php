<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockItemResource;
use App\Models\Vehicle;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlockHistoryController extends Controller
{
    public function __invoke(Vehicle $vehicle): AnonymousResourceCollection
    {
        $blockItems = $vehicle->getAttribute('blockItems');

        return BlockItemResource::collection($blockItems);
    }
}
