<?php

namespace App\Http\Controllers;

use App\Http\Resources\PDIResource;
use App\Models\Vehicle;
use App\Repositories\PDIRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PDIController extends CrudController
{

    protected bool $appendUserId = true;

    public function __construct(public readonly PDIRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PDIResource::class);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function store(Request $request)
    {
        $vehicle = Vehicle::query()->where('vin', $validatedData['vin'])->first();
        if (!$vehicle) {
            throw new ValidationException('شماره VIN وارد شده یافت نشد');
        }
        if (empty($vehicle->carModel->production)) {
            throw new Exception("لطفا برای مدل خودرو {$vehicle->getAttribute('carModel')->title} نوع تولید را مشخص کنید. ");
        }
        $rules = [
            'pdi_result' => 'required|string|max:255',
            'pdi_result_description' => 'required|string|max:250',
            'pdi_result_description_2' => 'nullable|string|max:100',
            'police_inspection_date' => 'required|string|max:255'
        ];
        if ($vehicle->carModel->production == 'imported') {
            $rules['model_year'] = 'required|string|max:255';
            $rules['engine_no'] = 'required|string|max:255';
        }
        $validatedData = $request->validate($rules);

        $validatedData['vin'] = $request->input('vehicle');

        $validatedData['user_id'] = Auth::id();

        try {
            DB::beginTransaction();
            $response = $this->sendPDIToErp($vehicle->getAttribute('vin'),$validatedData);

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

    private function sendPDIToErp(string $vin,array $data): array
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
