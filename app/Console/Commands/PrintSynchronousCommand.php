<?php

namespace App\Console\Commands;

use App\Models\Synchronous;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class PrintSynchronousCommand extends Command
{

    protected $signature = 'app:print-synchronous-command';



    public function handle()
    {

    }
}
