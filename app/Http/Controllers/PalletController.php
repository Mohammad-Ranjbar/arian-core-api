<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPalletRequest;
use App\Http\Requests\TransferPalletRequest;
use App\Http\Resources\PalletResource;
use App\Http\Resources\PalletTransactionResource;
use App\Models\Pallet;
use App\Models\PalletTransaction;
use App\Repositories\PalletRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PalletController extends CrudController
{

    /**
     * @param PalletRepository $repository
     */
    public function __construct(public readonly PalletRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PalletResource::class);
    }

    /**
     * @param AddPalletRequest $request
     * @return PalletTransactionResource
     */
    public function addPallet(AddPalletRequest $request): PalletTransactionResource
    {
        $transaction = PalletTransaction::query()->create($request->validated());

        return new PalletTransactionResource($transaction);
    }

    public function transferPallet(TransferPalletRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $FromLocationQty = PalletTransaction::query()
            ->where('pallet_id', $validatedData['pallet_id'])
            ->where('location_id', $validatedData['from_location_id'])->sum('qty');
        if ($validatedData['qty'] > $FromLocationQty) {
            return response()->json([
                'message' => 'Quantity out of stock',
            ]);
        }
        PalletTransaction::query()->create([
            'pallet_id' => $validatedData['pallet_id'],
            'location_id' => $validatedData['from_location_id'],
            'qty' => $validatedData['qty'] * -1,
        ]);
        PalletTransaction::query()->create([
            'pallet_id' => $validatedData['pallet_id'],
            'location_id' => $validatedData['to_location_id'],
            'qty' => $validatedData['qty'],
        ]);

        return response()->json([
            'message' => 'Pallet(s) transfered successfully',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function getPalletLocations(Pallet $pallet): JsonResponse
    {
        $this->authorize('pallet-locations');

        $sql = 'SELECT
    SUM(qty) AS qty , locations.title,location_id
FROM
    pallet_transactions
    LEFT JOIN locations ON pallet_transactions.location_id = locations.id
WHERE
    pallet_id = ?
    GROUP BY pallet_transactions.location_id';
        return response()->json(DB::select($sql, [$pallet->getAttribute('id')]));

    }

}
