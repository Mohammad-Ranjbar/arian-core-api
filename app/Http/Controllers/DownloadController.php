<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{

    public function __invoke(Request $request): BinaryFileResponse
    {

        $subPath = ltrim($request->get('path'), '/');

        if ($subPath === '' || str_contains($subPath, '..') || str_contains($subPath, "\0")) {
            abort(404);
        }

        $absolutePath = storage_path('app/public/' . $subPath);

        $realPath = realpath($absolutePath);

        if (
            $realPath === false ||
            !str_starts_with($realPath, storage_path('app/public'))
        ) {
            abort(404);
        }

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
