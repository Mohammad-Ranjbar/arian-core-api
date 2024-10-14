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
            return response()->download($path);
        }

        abort(404);
    }

    public function s3(Request $request)
    {
        return Storage::disk('s3')->download($request->get('path'));
    }
}
