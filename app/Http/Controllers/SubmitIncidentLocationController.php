<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SubmitIncidentLocationController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function getStopLocations(): JsonResponse
    {
        $this->authorize('get.stop.locations');
        /* @var $user User */
        $user = Auth::user();
        return response()->json($user->stations()->selectRaw('title,stations.id as location_id, "stations" as location_type , "x" as "group"')->get());


    }
}
