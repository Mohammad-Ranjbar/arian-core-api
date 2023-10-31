<?php

use Illuminate\Support\Facades\Route;

Route::any('{all}', function () {
    return phpinfo();
})->where(['all' => '.*']);
