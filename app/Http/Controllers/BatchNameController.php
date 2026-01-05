<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BatchNameController extends Controller
{

    public function __invoke(Request $request)
    {
        $data = [
            'batch1',
            'batch2',
            'batch3',
            'batch4',
        ];
        return response()->json($data);
    }
}
