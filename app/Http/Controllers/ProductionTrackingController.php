<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Laminas\Diactoros\Request;

class ProductionTrackingController extends Controller
{
    public function enter(Request $request, Vehicle $vehicle)
    {
        dd($vehicle);
    }
}
