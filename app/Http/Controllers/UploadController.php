<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'total_chunks' => 'required',
            'chunk_number' => 'required',
            'ack' => 'required',
            'ext'  => 'required',
        ]);

        $file = $request->file('file');

        $fileName = $request->get('ack').'_'.$request->get('chunk_number');
        $fileExtension = $file->getClientOriginalExtension();
        $path = $fileName.'.'.$fileExtension;

        Storage::disk('temp')->put($path, file_get_contents($file));

        if ($request->get('total_chunks') == $request->get('chunk_number')) {
            File::put(storage_path('app/upload/text.txt'), '');
            for ($i = 1; $i <= $request->get('total_chunks'); $i++) {
                $additionalData = File::get(storage_path('app/temp/'.$request->get('ack').'_'.$i.'.'.$fileExtension));
                File::append(storage_path('app/upload/text.txt'), $additionalData);
            }

        }
        return response()->json([
            'message' => 'uploaded'
        ]);

    }
}
