<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Station;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomLocationController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('get-custom-locations');
        if ($request->get('type') == 'evaluation') {
            $data = [
                [
                'custom_location_type' => Location::class,
                'custom_location_id' => 1,
                'title' => 'سایت تولیدی خمین'
            ]
            ];
        } elseif ($request->get('type') == 'qc') {
            $data = Station::query()->whereHas('users', fn($query) => $query->where('user_id', Auth::id()))
                ->get()->map(function ($station) {
                    return [
                        'custom_location_type' => Station::class,
                        'custom_location_id' => $station->id,
                        'title' => $station->title
                    ];
                });
        }

        return new JsonResponse(
            data: [
                'data' => $data
            ],
        );
    }
}
