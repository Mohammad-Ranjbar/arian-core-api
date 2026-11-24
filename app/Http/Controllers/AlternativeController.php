<?php

namespace App\Http\Controllers;

use App\Jobs\ExportJob;
use App\Models\Job;
use App\Services\AlternativeFilters;
use App\Http\Requests\AlternativeReportExcelRequest;
use App\Http\Requests\SendAlternativeRequest;
use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
use App\Repositories\AlternativeRepository;
// use App\Services\Erp\ErpWsdlClient;
use App\Services\Erp\RestApi\CreateTransferZonesService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AlternativeController extends CrudController
{

    public function __construct(public readonly AlternativeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeResource::class);
    }

    public function listAlternatives()
    {
        $alternativeGroupUser = AlternativeCategory::query()
            ->where('user_id', Auth::id())
            ->first();
        if (empty($alternativeGroupUser)) {
            return response()->json([
                'message' => 'الترنانتیوی به کاربری شما تخصیص داده نشده',
            ]);
        }
        $alternativeGroupItems = $alternativeGroupUser->items()->select('item_no')->get()->toArray();

        $alternatives = Alternative::query()
            ->whereIn('item_no', $alternativeGroupItems);

        return $this->filteredAlternatives($alternatives);
    }

    public function listAllAlternatives()
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);

        return $this->filteredAlternatives(Alternative::query());
    }

    private function filteredAlternatives(\Illuminate\Database\Eloquent\Builder $alternatives)
    {
        $alternatives = AlternativeFilters::apply($alternatives, request()->all())->latest()
            ->paginate(request('per_page') ?? 10);

        return AlternativeResource::collection($alternatives);
    }

    public function reportExcel(AlternativeReportExcelRequest $request): JsonResponse
    {
        $job = Job::query()->create(['type' => 'export-alternatives', 'user_id' => Auth::id(), 'data' => $request->validated()]);
        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json(['message' => 'export job created', 'data' => [
            'uuid' => $job->getAttribute('uuid'),
            'tracking_url' => route('job.tracking', $job->getAttribute('uuid')),
        ]]);
    }

    public function sendAlternatives(Alternative $alternative, SendAlternativeRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $alternative->update([
                'user_id' => Auth::id(),
            ]);
            if (empty($alternative->getAttribute('send_at'))) {
                $alternative->update(['send_at' => now()]);
            }
            if (empty($alternative)) {
                throw new Exception('آيتم مورد نظر پیدا نشد');
            }

            if (empty($alternative->getAttribute('document_number'))) {
                $documentNo = 'ALT-' . Str::random(15);
                $alternative->update([
                    'document_number' => $documentNo,
                ]);
            }

            $variants = $alternative->variants
                ->pluck('variant_code')
                ->countBy();

            $qtyVariants = [];
            foreach ($alternative->variants as $variant) {
                $qtyVariants[] = $variant->quantity;
            }

            $sumQty = implode(',', $qtyVariants);

            $parameters = [
                'itemNoArray' => collect()
                    ->times(
                        $variants->count(),
                        fn() => $alternative->getAttribute('item_no')
                    )
                    ->implode(','),


                'numberItemsP' => $variants->count(),

                'itemQtyArray' => $sumQty,

                'fromLocationTypeP' => 'Central',

                'toLacationTypeP' => 'Production',

                'variantArray' => $variants->keys()->implode(','),

                'crossRefArray' => implode(',', array_fill(
                    0,
                    $variants->count(),
                    ''
                )),

                'makeCodeP' => $validatedData['make_code_reference'],

                'documentNo' => $alternative->getAttribute('document_number'),
            ];
            logger($parameters);
            // $response = ErpWsdlClient::run(
            //     config('erp.send_alternative'),
            //     'CreateTransfer_Zone',
            //     $parameters
            // );

            $response = resolve(CreateTransferZonesService::class)->send(
                itemNoArray: $parameters['itemNoArray'],
                itemQtyArray: $parameters['itemQtyArray'],
                numberItems: $parameters['numberItemsP'],
                fromLocationType: $parameters['fromLocationTypeP'],
                toLacationType: $parameters['toLacationTypeP'],
                variantArray: $parameters['variantArray'],
                crossRefArray: $parameters['crossRefArray'],
                makeCode: $parameters['makeCodeP'],
                documentNo: $parameters['documentNo'],
                model: $alternative,
            )->sync();
            if (! $response['success']) {
                $alternative->update([
                    'failed_at' => now(),
                    'status' => 'failed',
                    'data' => $response['error'],
                ]);
                return $this->responseError($response['error']['message'], $response['http_status']);
            }

            $alternative->update([
                'success_at' => now(),
                'status' => 'success',
                'data' => $response['data'],
            ]);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
