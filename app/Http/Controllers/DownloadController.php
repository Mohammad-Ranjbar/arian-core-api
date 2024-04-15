<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{

    public function __invoke(Request $request)
    {
        $path = storage_path('app/public/' . $request->get('path'));
        if (file_exists($path)) {
            return response()->download($path);
        }

        abort(404);
    }
}
