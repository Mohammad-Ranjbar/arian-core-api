<?php

namespace App\Http\Controllers;

use App\Http\Resources\PDIResource;
use App\Models\PDI;
use App\Models\Vehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PDIController extends Controller
{

    public function index(Vehicle $vehicle): AnonymousResourceCollection
    {
        $data = PDI::query()->where('vin', $vehicle->vin)->get();

        return PDIResource::collection($data);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function store(Vehicle $vehicle, Request $request)
    {
        if (empty($vehicle->carModel->production)) {
            throw new Exception("لطفا برای مدل خودرو {$vehicle->getAttribute('carModel')->title} نوع تولید را مشخص کنید. ");
        }
        $rules = [
            'pdi_result' => 'required_with:pdi_result_description|required_with:pdi_result_description_2|string|max:255',
            'pdi_result_description' => 'required_with:pdi_result|string|max:250',
            'pdi_result_description_2' => 'nullable|string|max:100',
            'police_inspection_date' => 'nullable|string|max:255'
        ];
        if ($vehicle->carModel->production == 'imported') {
            $rules['model_year'] = 'nullable|string|max:255';
            $rules['engine_no'] = 'nullable|string|max:255';
        }

        $validatedData = $request->validate($rules);

        if (empty(array_values($validatedData))) {
            return response()->json([
                'message' => 'مقادیر وارد شده معتبر نمیباشد'
            ], 422);
        }
        $validatedData['vin'] = $request->input('vehicle');

        $validatedData['user_id'] = Auth::id();

        try {
            DB::beginTransaction();
            $response = $this->sendPDIToErp($vehicle->getAttribute('vin'), $validatedData);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }

            $saveItem = $this->repository->store($validatedData);

            DB::commit();
            return new $this->resource($saveItem);
        } catch (Throwable $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    private function sendPDIToErp(string $vin, array $data): array
    {
        return ErpWsdlClient::run(config('erp.pdi_result'), 'ImportVehicleInformation',
            [
                'vINP' => $vin,
                'pDIResultP' => $data['pdi_result'],
                'pDIDescriptionP' => $data['pdi_result_description'],
                'pDIResultDescP' => $data['pdi_result_description_2'],
                'engineNoP' => $data['engine_no'] ?? '',
                'modelYearP' => $data['model_year'] ?? '',
                'policeInspectionDateP' => $data['police_inspection_date'],
            ]);

    }

}
