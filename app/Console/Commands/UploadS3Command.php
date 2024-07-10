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
        Storage::disk('s3')->putFile($this->argument('destination'), Storage::path($this->argument('source')));
    }
}
