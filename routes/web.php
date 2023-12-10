<?php

use Illuminate\Support\Facades\Route;


Route::any('/', function () {
    return response()->json([
        'version' => app()->version(),
        'env'     => app()->environment()
    ]);
});


Route::get('get-template-incident',[\App\Http\Controllers\IncidentController::class,'getTemplate']);