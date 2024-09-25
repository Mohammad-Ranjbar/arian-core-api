<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShortageItemResource;
use App\Models\ShortageItem;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ShortageController extends Controller
{


    /**
     * @throws ContainerExceptionInterface
     * @throws AuthorizationException
     * @throws NotFoundExceptionInterface
     */
    public function index(Vehicle $vehicle): AnonymousResourceCollection
    {
        $this->authorize('shortages.index');
        $perPage = request()->get('per_page', 15);
        return ShortageItemResource::collection($vehicle->shortageItems()->paginate($perPage));
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Vehicle $vehicle, Request $request): ShortageItemResource
    {
        $this->authorize('shortages.store');
        $validatedData = $request->validate([
            'erp_product_item_id' => [
                'required',
                'exists:erp_product_items,id',
                Rule::unique('shortage_items', 'erp_product_item_id')
                    ->where('vin', $vehicle->getAttribute('vin'))
                    ->where('status', 'pending')
            ],
        ]);


        return new ShortageItemResource(ShortageItem::query()->create([
            'erp_product_item_id' => $validatedData['erp_product_item_id'],
            'user_id' => Auth::id(),
            'vin' => $vehicle->getAttribute('vin')
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Vehicle $vehicle, ShortageItem $shortage): ShortageItemResource
    {
        $this->authorize('shortages.update');

        $validatedData = $request->validate([
            'status' => ['required', 'in:done',
                Rule::unique('shortage_items')
                    ->where('vin', $vehicle->getAttribute('vin'))
                    ->where('id', $shortage->getAttribute('id'))
            ],
        ]);

        $shortage->update($validatedData);

        return new ShortageItemResource($shortage);
    }

}
