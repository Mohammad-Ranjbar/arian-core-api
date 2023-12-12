<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class SubmitIncidentLocationController extends Controller
{
    public function getStopLocations(Request $request): JsonResponse
    {
        /* @var $user User */
        $user = Auth::user();
        return response()->json($user->stations()->selectRaw('title,stations.id as location_id, "stations" as location_type , "x" as "group"')->get());


    }
}
