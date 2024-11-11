<?php

namespace App\Http\Controllers;

use App\Http\Resources\PalletTransactionResource;
use App\Models\PalletTransaction;
use Illuminate\Http\Request;

class PalletTransactionController extends Controller
{

    public function index()
    {
        return response()->json(PalletTransactionResource::collection(PalletTransaction::query()->latest()->get()));
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
