<?php

use Illuminate\Support\Facades\Route;

Route::get('/t', function () {
    return view('pdf.compliance.SX5G');
});
Route::any('/', function () {
    return response()->json([
        'version' => app()->version(),
        'env'     => app()->environment()
    ]);
});

