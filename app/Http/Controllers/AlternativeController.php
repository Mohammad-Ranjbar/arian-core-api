<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendAlternativeRequest;
use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
use App\Models\AlternativeItem;
use App\Repositories\AlternativeRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Support\Facades\Auth;
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

        $alternatives = Alternative::query()->whereIn('item_no', $alternativeGroupItems)->paginate(25);

        return AlternativeResource::collection($alternatives);
    }

    public function sendAlternatives(Alternative $alternative,SendAlternativeRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $alternative->update([
                'user_id' => Auth::id(),
            ]);
            if (empty($alternative->getAttribute('send_at'))){
                $alternative->update(['send_at' => now()]);
            }
            if (empty($alternative)) {
                throw new Exception('آيتم مورد نظر پیدا نشد');
            }

            if (empty($alternative->getAttribute('document_number'))){
                $documentNo = 'ALT-' . Str::random(15);
                $alternative->update([
                    'document_number' => $documentNo,
                ]);
            }

            $variants = $alternative->variants
                ->pluck('variant_code')
                ->countBy();
            foreach ($alternative->variants as $variant) {
                $qtyVariants[] = $variant->quantity;
            }
            $sumQty = implode(',',$qtyVariants);
            dd($sumQty);
            $parameters = [
                'itemNoArray' => collect()
                    ->times(
                        $variants->count(),
                        fn () => $alternative->getAttribute('item_no')
                    )
                    ->implode(','),



                'numberItemsP' => $variants->count(),

                'itemQtyArray' => $variants->values()->implode(','),

                'fromLocationTypeP' => 'CENTRAL',

                'toLacationTypeP' => 'PRODUCTION',

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
            $response = ErpWsdlClient::run(
                config('erp.send_alternative'),
                'CreateTransfer_Zone',
                $parameters
            );
            if ($response['status'] !== 'ok') {
                $alternative->update([
                    'failed_at' => now(),
                    'status' => 'failed',
                    'data' => $response['message'],
                ]);
                return $this->responseError($response['message'], 400);
            }

            $alternative->update([
                'success_at' => now(),
                'status' => 'success',
                'data' => $response['message'],
            ]);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
