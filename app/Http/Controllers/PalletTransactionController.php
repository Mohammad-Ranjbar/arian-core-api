<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePalletTransactionRequest;
use App\Http\Resources\PalletTransactionResource;
use App\Models\PalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PalletTransactionController extends Controller
{

    public function index(): AnonymousResourceCollection
    {
        return PalletTransactionResource::collection(PalletTransaction::query()->latest()->paginate());
    }


    public function store(StorePalletTransactionRequest $request): PalletTransactionResource
    {
        $data = PalletTransaction::query()->create($request->validated());

        return PalletTransactionResource::make($data);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(PalletTransaction $palletTransaction)
    {
        $palletTransaction->delete();
        return response()->json([
            'message' => 'PalletTransaction deleted successfully!'
        ]);
    }
}
