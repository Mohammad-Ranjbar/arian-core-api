<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Http\Requests\ImportFileRequest;
use App\Http\Requests\StorePDIRequest;
use App\Http\Resources\PDIResource;
use App\Jobs\ImportJob;
use App\Models\Job;
use App\Models\PDI;
use App\Models\Vehicle;
use App\Services\Erp\RestApi\ImportVehiclesService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PDIController extends Controller
{

    public function index(Vehicle $vehicle): AnonymousResourceCollection
    {
        $data = PDI::query()->where('vin', $vehicle->vin)->get();

        return PDIResource::collection($data);
    }


    /**
     * @throws Throwable
     */
    public function store(Vehicle $vehicle, StorePDIRequest $request): PDIResource|JsonResponse
    {
        if (empty($vehicle->carModel->production)) {
            throw new Exception("لطفا برای مدل خودرو {$vehicle->getAttribute('carModel')->title} نوع تولید را مشخص کنید. ");
        }
        $validatedData = $request->validated();

        if (empty(array_values($validatedData))) {
            return response()->json([
                'message' => 'حداقل یک مورد از اطلاعات PDI را وارد کنید.'
            ], 422);
        }
        $validatedData['vin'] = $vehicle->vin;

        $validatedData['user_id'] = Auth::id();

        try {
            DB::beginTransaction();
            $response = self::sendPDIToErp($vehicle->getAttribute('vin'), $validatedData);

            if ($response['status'] !== 'ok') {
                DB::rollBack();
                return $this->responseError($response['message'], 400);
            }
            $saveItem = PDI::query()->create($validatedData);


            DB::commit();

            return PDIResource::make($saveItem);
        } catch (Throwable $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * @throws Throwable
     */
    public static function sendPDIToErp(string $vin, array $data): array
    {
        // return ErpWsdlClient::run(config('erp.pdi_result'), 'ImportVehicleInformation',
        //     [
        //         'vINP' => $vin,
        //         'pDIResultP' => $data['pdi_result'] ?? '',
        //         'pDIDescriptionP' => $data['pdi_result_description'] ?? '',
        //         'pDIResultDescP' => $data['pdi_result_description_2'] ?? '',
        //         'engineNoP' => $data['engine_no'] ?? '',
        //         'modelYearP' => $data['model_year'] ?? '',
        //         'policeInspectionDateP' => $data['police_inspection_date'] ?? '',
        //     ]);

        $result = resolve(ImportVehiclesService::class)->send($vin, $data, userId: $data['user_id'] ?? null)->sync();

        return [
            'status' => $result['success'] ? 'ok' : 'error',
            'message' => $result['error']['message'] ?? '',
        ];
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getTemplateEngine(): BinaryFileResponse
    {
        $headers = PDI::HEADING_ENGINE;
        $export = new TemplateExport($headers);
        return Excel::download($export, 'template-for-engines' . time() . '.xlsx');
    }

    public function importEngine(ImportFileRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-engine';
        $validatedData['data'] = [
            'file' => $validatedData['file'],
            'user_id' => Auth::id(),
        ];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'import job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    public function importPolice(ImportFileRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-police';
        $validatedData['data'] = [
            'file' => $validatedData['file'],
            'user_id' => Auth::id(),
        ];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'import job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    public function importPdi(ImportFileRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-pdi';
        $validatedData['data'] = [
            'file' => $validatedData['file'],
            'user_id' => Auth::id(),
        ];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'import job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getTemplatePolice(): BinaryFileResponse
    {
        $headers = PDI::HEADING_POLICE;
        $export = new TemplateExport($headers);
        return Excel::download($export, 'template-for-police' . time() . '.xlsx');
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getTemplatePdi(): BinaryFileResponse
    {
        $headers = PDI::HEADING_PDI;
        $export = new TemplateExport($headers);
        return Excel::download($export, 'template-for-PDI' . time() . '.xlsx');
    }
}
