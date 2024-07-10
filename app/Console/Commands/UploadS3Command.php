<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadS3Command extends Command
{

    protected $signature = 'upload:s3 {path} {fileName}';


    protected $description = 'create backup database mysql and store in S3';


    public function handle(): void
    {
        $path = $this->argument('path');
        $fileName = $this->argument('fileName');
        if(File::exists($path.$fileName)){
           $fileNameUploaded = Storage::disk('s3')
                ->putFile('/backups/mysql/'.$path, $fileName);
           if ($file){
               $this->info('backup file is uploaded to S3 Arvan Cloud filename is:'.$fileNameUploaded);
           }
            $this->error('error when backup file is uploading');
        }
        $this->error($fileName.' does not exists in path '.$path);



    }
}
