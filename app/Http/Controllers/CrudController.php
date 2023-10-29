<?php

namespace App\Http\Controllers;

use App\libs\BaseRepository;
use Illuminate\Http\Request;

class CrudController extends Controller
{
    public $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->repository->collect($this->repository->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate($this->repository->rules);
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
        $request->validate($this->repository->rules);
        return $this->repository->update($id, $request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->repository->destroy($id);
    }


}
