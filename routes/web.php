<?php

use Illuminate\Support\Facades\Route;

Route::any('/login',function (){
    return response()->json([
        'message' => 'unauthenticated'
    ],401);
})->name('login');
Route::any('/', function () {
    return response()->json([
        'version' => app()->version(),
        'env'     => app()->environment()
    ]);
});
Route::view('test','test');

Route::get('get-template-incident',[\App\Http\Controllers\IncidentController::class,'getTemplate']);