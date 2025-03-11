<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function s3(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($request) {
            $stream = Storage::disk('s3')->readStream($request->get('path'));
            fpassthru($stream);
            fclose($stream);
        });

        $response->headers->set('Content-Type', Storage::disk('s3')->mimeType($request->get('path')));
        return $response;
    }
}
