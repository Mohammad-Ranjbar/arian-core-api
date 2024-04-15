<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{

    public function __invoke(Request $request)
    {
        return Storage::download($request->get('path'));
    }
}
