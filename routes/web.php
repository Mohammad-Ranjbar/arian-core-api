<?php

use App\Http\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;


Route::any('/', function () {
    return response()->json([
        'version' => app()->version(),
        'env'     => app()->environment()
    ]);
});

Route::view('/test','test');

Route::get('get-template-incident', [IncidentController::class, 'getTemplate']);