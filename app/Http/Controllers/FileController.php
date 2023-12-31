<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Repositories\FileRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class FileController extends CrudController
{

    public function __construct(public readonly FileRepository $repository)
    {
        $this->setResource(FileResource::class);
    }



    public function store(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $data = $this->saveFile($save->getFile());

            // Store the file information in Redis
            $fileInfo = [
                'user_id' => Auth::id(),
                'mime'    => $data['mime_type'],
                'path'    => $data['path'].'/'.$data['name']
            ];
            $fileKey = 'file:'.uniqid(); // Generate a unique key for the file
            Redis::hmset($fileKey, $fileInfo);

            // Return success response
            $data['file_key'] = $fileKey;
            $data['status'] = 'done';
            $data['percent'] = 100;

            return response()->json($data);
        } else {
            $handler = $save->handler();
            $progressKey = 'upload_progress:'.$save->handler()->getKey();
            Redis::set($progressKey, $handler->getPercentageDone());

            // Return pending status
            return response()->json(["percent" => $handler->getPercentageDone(), "status" => 'pending']);
        }
    }


    /**
     * @param  UploadedFile  $file
     * @return array
     */
    protected function saveFile(UploadedFile $file): array
    {
        $fileName = $this->createFilename($file);

        $mime = str_replace('/', '-', $file->getMimeType());

        $dateFolder = date("Y-W");

        $filePath = "uploads/$dateFolder";
        $finalPath = storage_path("app/public/".$filePath);
        $file->move($finalPath, $fileName);

        return ['path' => 'storage/'.$filePath, 'name' => $fileName, 'mime_type' => $mime];
    }

    /**
     * @param  UploadedFile  $file
     * @return string
     */
    protected function createFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid()."_".md5(time()).".".$extension;
    }

}
