<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendAlternativeRequest;
use App\Http\Resources\AlternativeResource;
use App\Models\Alternative;
use App\Models\AlternativeCategory;
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

        $alternativeGroupItems = $alternativeGroupUser->items()->select('item_no')->get()->toArray();

        $alternatives = Alternative::query()->whereIn('item_no', $alternativeGroupItems)->paginate(25);

        return AlternativeResource::collection($alternatives);
    }

    public function sendAlternatives(SendAlternativeRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $alternative = Alternative::query()
                ->where('item_no', $validatedData['item_no'])
                ->first();
            $alternative->update([
                'user_id' => Auth::id(),
            ]);
            if (empty($alternative->getAttribute('send_at'))){
                $alternative->update(['send_at' => now()]);
            }
            if (empty($alternative)) {
                throw new Exception('آيتم مورد نظر پیدا نشد');
            }

            if (empty($alternative->getAttribute('document_no'))){
                $documentNo = 'ALT-' . Str::random(15);
                $alternative->update([
                    'document_no' => $documentNo,
                ]);
            }
            $parameters = [
                'itemNoArray' => $validatedData['item_no'],
                'itemQtyArray' => $alternative->getAttribute('packing_qty'),
                'numberItemsP' => 1,
                'fromLocationTypeP' => 'CENTRAL',
                'toLacationTypeP' => 'PRODUCTION',
                'variantArray' => $alternative->getAttribute('variant_code'),
                'crossRefArray' => $alternative->getAttribute('item_no'),
                'makeCodeP' => $validatedData['make_code_reference'],
                'documentNo' => $alternative->getAttribute('document_no'),
            ];
            $response = ErpWsdlClient::run(
                config('erp.send_alternative'),
                'CreateTransfer_Zone',
                $parameters
            );
            if ($response['status'] !== 'ok') {
                $alternative->update([
                    'failed_at' => now(),
                    'data' => $response['message'],
                ]);
                return $this->responseError($response['message'], 400);
            }

            $alternative->update([
                'success_at' => now(),
                'data' => $response['message'],
            ]);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
