<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadS3Command extends Command
{

    protected $signature = 'upload:s3 {source} {destination}';


    protected $description = 'create backup database mysql and store in S3';


    public function handle(): void
    {
        $source = $this->argument('source');
        $destination = $this->argument('destination');
        $this->info($source);
        $this->info($destination);
        if (Storage::exists($source)) {
            $fileNameUploaded = Storage::disk('s3')
                ->putFile($destination, Storage::get($source));
            if ($fileNameUploaded) {
                $this->info('backup file is uploaded to S3 Arvan Cloud filename is:'.$fileNameUploaded);
            }
            $this->error('error when backup file is uploading');
        }
        $this->error($source.' does not exists');


    }
}
