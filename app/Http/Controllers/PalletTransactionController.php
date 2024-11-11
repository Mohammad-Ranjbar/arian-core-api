<?php

namespace App\Http\Controllers;

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


    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
