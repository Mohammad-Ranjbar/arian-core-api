<?php

namespace App\Http\Controllers;

use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Filters\ItemNoFilter;
use App\Http\Requests\SendAlternativeRequest;
use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
use App\Repositories\AlternativeRepository;
// use App\Services\Erp\ErpWsdlClient;
use App\Services\Erp\RestApi\CreateTransferZonesService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;
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

        if ($status = request('status')) {
            if ($status === 'success') {
                $alternatives->whereNotNull('success_at')
                    ->whereHas('alternativeItem', fn($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            } elseif ($status === 'send') {
                $alternatives->whereNull('success_at')
                    ->whereHas('alternativeItem', fn($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            } elseif ($status === 'failed') {
                $alternatives->whereNotNull('failed_at')->whereNull('success_at')->whereHas('alternativeItem', fn($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            }
        }

        $alternatives = Pipeline::send($alternatives)
            ->through([
                ItemNoFilter::class,
                CreatedAtFromFilter::class,
                CreatedAtToFilter::class,

            ])->thenReturn()->latest()
            ->paginate(request('per_page') ?? 10);

        return AlternativeResource::collection($alternatives);
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
            );
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
