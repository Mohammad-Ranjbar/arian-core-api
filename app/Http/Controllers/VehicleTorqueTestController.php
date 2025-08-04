<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleTorqueRequest;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Models\Screw;
use App\Models\TorqueCoefficient;
use App\Models\VehicleTorqueTest;
use Exception;
use Illuminate\Http\JsonResponse;

class VehicleTorqueTestController extends Controller
{

    public function __invoke(StoreVehicleTorqueRequest $request): VehicleTorqueTestResource|JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $const = TorqueCoefficient::query()->where('is_active', 1)->first();

            if (empty($const)) {
                return $this->responseError('cant find any active torque coefficent');
            }
            $validatedData['torque_coefficient_id'] = $const->getAttribute('id');

            $screw = Screw::query()->find($validatedData['screw_id']);
            $range = [
                $screw->getAttribute('min') * $const->getAttribute('low_3'),
                $screw->getAttribute('min') * $const->getAttribute('low_2'),
                $screw->getAttribute('min') * $const->getAttribute('low_1'),
                $screw->getAttribute('min'),
                $screw->getAttribute('max'),
                $screw->getAttribute('max') * $const->getAttribute('high_1'),
                $screw->getAttribute('max') * $const->getAttribute('high_2'),
                $screw->getAttribute('max') * $const->getAttribute('high_3'),
                $screw->getAttribute('max') * $const->getAttribute('high_4'),
            ];

            $colors = [
                'red' => '#ff0000',
                'orange' => '#ff6600',
                'yellow' => '#ffff00',
                'green' => '#00ff00',
            ];
            if ($validatedData['value'] < $range[0]) {
                $validatedData['range_color'] = 'l4';
                $validatedData['range_code'] = $colors['red'];
            }


            if ($validatedData['value'] >= $range[0] && $validatedData['value'] < $range[1]) {
                $validatedData['range_color'] = 'l3';
                $validatedData['range_code'] = $colors['red'];
            }

            if ($validatedData['value'] >= $range[1] && $validatedData['value'] < $range[2]) {
                $validatedData['range_color'] = 'l2';
                $validatedData['range_code'] = $colors['orange'];
            }

            if ($validatedData['value'] >= $range[2] && $validatedData['value'] < $range[3]) {
                $validatedData['range_color'] = 'l1';
                $validatedData['range_code'] = $colors['yellow'];
            }

            if ($validatedData['value'] > $range[3] && $validatedData['value'] <= $range[4]) {
                $validatedData['range_color'] = 'n';
                $validatedData['range_code'] = $colors['green'];
            }

            if ($validatedData['value'] > $range[4] && $validatedData['value'] <= $range[5]) {
                $validatedData['range_color'] = 'h1';
                $validatedData['range_code'] = $colors['green'];
            }

            if ($validatedData['value'] > $range[5] && $validatedData['value'] <= $range[6]) {
                $validatedData['range_color'] = 'h2';
                $validatedData['range_code'] = $colors['yellow'];
            }
            if ($validatedData['value'] > $range[6] && $validatedData['value'] <= $range[7]) {
                $validatedData['range_color'] = 'h3';
                $validatedData['range_code'] = $colors['orange'];
            }
            if ($validatedData['value'] > $range[7] && $validatedData['value'] <= $range[8]) {
                $validatedData['range_color'] = 'h4';
                $validatedData['range_code'] = $colors['red'];
            }
            if ($validatedData['value'] > $range[8]) {
                $validatedData['range_color'] = 'h5';
                $validatedData['range_code'] = $colors['red'];
            }


            $data = VehicleTorqueTest::query()->create($validatedData);

            return new VehicleTorqueTestResource($data);
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }

    }
}
