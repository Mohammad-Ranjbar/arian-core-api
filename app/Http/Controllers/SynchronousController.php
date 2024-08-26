<?php

namespace App\Http\Controllers;

use App\Http\Resources\SynchronousResource;
use App\Models\Synchronous;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SynchronousController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('index.synchronous');

        $data = Synchronous::query()->with('synchronousType')->latest()->get();

        return SynchronousResource::collection($data);
    }

    public function closeSynchronous(Synchronous $synchronous): SynchronousResource
    {
        $synchronous->update([
            'capacity' => 0
        ]);

        return new SynchronousResource($synchronous->fresh());
    }

    public function printSynchronous(Synchronous $synchronous)
    {

    }
}
