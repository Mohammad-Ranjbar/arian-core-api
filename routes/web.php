<?php

use App\Http\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;


Route::get('/test2',function (){
    return \Illuminate\Support\Facades\Auth::locations();
});

Route::any('/', function () {
    return response()->json([
        'version' => app()->version(),
        'env'     => app()->environment()
    ]);
});

