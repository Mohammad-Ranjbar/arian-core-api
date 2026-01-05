<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BatchNameController extends Controller
{

    public function __invoke(Request $request)
    {
        $data = [
            'LAMARI',
            'JAC',
            'SHACMOTO',
            'CHEMICAL'
        ];

        $result = array_map(function($item) {
            return [
                'id' => $item,
                'title' => $item
            ];
        }, $data);
        return response()->json($result);
    }
}
