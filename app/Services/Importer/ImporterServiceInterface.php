<?php

namespace App\Services\Importer;

use App\Models\File;

interface ImporterServiceInterface
{

    public function import($jobId, File $file);

}