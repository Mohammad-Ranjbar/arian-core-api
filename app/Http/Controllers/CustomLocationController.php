<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Station;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomLocationController extends Controller
{

    /**
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('get-custom-locations');
        return new JsonResponse(
            data: [
                'data' => [
                    [
                        'custom_location_type' => Location::class,
                        'custom_location_id'   => 1,
                        'title'                => 'سایت تولیدی خمین'
                    ]
                ]
            ],
        );
    }
}
