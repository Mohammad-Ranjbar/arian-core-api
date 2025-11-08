<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{

    public function __invoke(Request $request)
    {
        // 1. Get the requested sub-path (e.g. "images/avatar.png")
        $subPath = ltrim($request->get('path'), '/');

        // 2. Reject empty or obviously malicious input
        if ($subPath === '' || str_contains($subPath, '..') || str_contains($subPath, "\0")) {
            abort(404);
        }

        // 3. Build the **absolute** path inside the public storage folder
        $absolutePath = storage_path('app/public/' . $subPath);

        // 4. Canonicalize the path and make sure it is still inside the allowed directory
        $realPath = realpath($absolutePath);

        if (
            $realPath === false ||                                   // file does not exist
            ! str_starts_with($realPath, storage_path('app/public')) // outside allowed dir
        ) {
            abort(404);
        }

        // 5. Serve the file (Laravel will set correct headers)
        return response()->download($realPath);
//        $path = storage_path('app/public/' . $request->get('path'));
//        if (file_exists($path)) {
//            return response()->download($path);
//        }
//
//        abort(404);
    }

    public function s3(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($request) {
            $stream = Storage::disk('s3')->readStream($request->get('path'));
            fpassthru($stream);
            fclose($stream);
        });

        $fileName = basename($request->get('path'));

        $response->headers->set('Content-Type', Storage::disk('s3')->mimeType($request->get('path')));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
