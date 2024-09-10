<?php

namespace App\Http\Controllers;

use App\Filters\SynchronousProductionLineIdFilter;
use App\Filters\SynchronousTitleFilter;
use App\Http\Resources\SynchronousResource;
use App\Models\Synchronous;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;

class SynchronousController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('index.synchronous');

       $data = Pipeline::send(Synchronous::query()->with('synchronousType')->latest())
           ->through([
               SynchronousProductionLineIdFilter::class,
               SynchronousTitleFilter::class
           ])
            ->thenReturn()->paginate(request()->per_page ?? 15);


        return SynchronousResource::collection($data);
    }

    public function closeSynchronous(Synchronous $synchronous): SynchronousResource
    {

        $synchronous->update([
            'capacity' => 0
        ]);

        return new SynchronousResource($synchronous);
    }

    public function printSynchronous(Synchronous $synchronous): SynchronousResource
    {

        return new SynchronousResource($synchronous);
    }
}
