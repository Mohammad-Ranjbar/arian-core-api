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
        return response()->json($data);
    }
}
