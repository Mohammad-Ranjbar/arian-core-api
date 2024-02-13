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

Route::get('/tr',function (){
    function searchNestedArray($array, $key, $value)
    {
        $results = [];
        foreach ($array as $subarray) {
            if (is_array($subarray) && array_key_exists($key, $subarray) && $subarray[$key] === $value) {
                $results[] = $subarray;
            } elseif (is_array($subarray)) {
                $results = array_merge($results, searchNestedArray($subarray, $key, $value));
            }
        }
        return $results;
    }
    $data = [
        ['id' => 1, 'name' => 'John', 'details' => ['age' => 25, 'city' => 'New York'],'etc' => ['city' => 'New York']],
        ['id' => 2, 'name' => 'Alice', 'details' => ['age' => 30, 'city' => 'Los Angeles']],
    ];

    $result = searchNestedArray($data, 'city', 'New York');

    print_r($result);
});