<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliverVehicleToCustomerController extends Controller
{
    public function get(Request $request,Vehicle $vehicle)
    {
        $information = [
            'vehicle' => new VehicleResource($vehicle),
            'customer' => [
                'name' => 'میثم',
                'national_code' => 'xxxx',
                'mobile' => 'xxxxx',
                'customer_code' => 'xx',
            ],
            'files' => [
                [
                    'title' => 'file 1',
                    'url' => 'http://file1.pdf',
                ],
                [
                    'title' => 'file 2',
                    'url' => 'http://file2.pdf',
                ],
                [
                    'title' => 'file 3',
                    'url' => 'http://file2.pdf',
                ],
                [
                    'title' => 'file 4',
                    'url' => 'http://file2.pdf',
                ],
            ],


        ];

        return response()->json($information);
    }

    public function set(Request $request, Vehicle $vehicle)
    {

      return  $vehicle->update([
            'files' => $request->get('files'),
            'delivered_customer_at' => Carbon::now()
        ]);
    }
}
