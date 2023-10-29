<?php

namespace App\Http\Controllers;

use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Filters\IdFilter;
use App\Filters\NameFilter;
use App\Filters\TitleFilter;
use App\libs\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;

class CrudController extends Controller
{
    public BaseRepository $repository;
    public array          $filters = [
        NameFilter::class,
        TitleFilter::class,
        CreatedAtFromFilter::class,
        CreatedAtToFilter::class,
        IdFilter::class
    ];

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Pipeline::send($this->repository->model)->through($this->filters)->thenReturn()->paginate(request()->paginate ?? 15);
        return $this->repository->collect($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate($this->repository->getCreateRules());
        return new $this->repository->resource($this->repository->store($request->toArray()));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return new $this->repository->resource($this->repository->show($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate($this->repository->getUpdateRules());
        return $this->repository->update($id, $request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    public function applyAuthorizeResource()
    {
        $this->authorizeResource(
            $this->repository->model->getMorphClass(),
            $this->repository->model->getMorphClass()
        );
    }

}
