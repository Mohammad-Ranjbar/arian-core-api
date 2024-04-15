<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{

    public function __invoke(Request $request)
    {
        $path = storage_path('app/public/' . $request->get('path'));
        if (file_exists($path)) {
            return Storage::download($request->get('path'));
        }

        abort(404);
    }
}
