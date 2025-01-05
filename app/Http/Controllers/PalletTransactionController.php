<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePalletTransactionRequest;
use App\Http\Resources\PalletTransactionResource;
use App\Models\PalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PalletTransactionController extends Controller
{

    public function index(): AnonymousResourceCollection
    {
        return PalletTransactionResource::collection(
            PalletTransaction::query()
                ->latest()
                ->when(request()->filled('create_at_from'),function ($q){
                    return $q->where('created_at','>=',request()->get('create_at_from'));
                })
                ->when(request()->filled('created_at_to'),function ($q){
                    return $q->where('created_at','<=',request()->get('created_at_to'));
                })
                ->when(request()->filled('qty'),function ($q){
                    return $q->where('qty',request()->get('qty'));
                })
                ->paginate(request()->per_page ?? 15)
        );
    }


    public function store(StorePalletTransactionRequest $request): PalletTransactionResource|JsonResponse
    {
        if ($request->get('qty') < 0) {
            $sumQty = PalletTransaction::query()->where('pallet_id', $request->get('pallet_id'))
                ->where('location_id', $request->get('location_id'))->sum('qty');
            if ($sumQty < abs($request->get('qty'))) {
                return response()->json([
                    'message' => 'please check location Qty'
                ],422);
            }
        }
        $data = PalletTransaction::query()->create($request->validated());

        return PalletTransactionResource::make($data);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(PalletTransaction $palletTransaction): JsonResponse
    {
        $palletTransaction->delete();
        return response()->json([
            'message' => 'PalletTransaction deleted successfully!'
        ]);
    }
}
