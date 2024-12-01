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

        /** @var Synchronous $synchronous */
        $synchronous = Synchronous::query()->find(1233);
        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'tempDir'      => storage_path('app/mpdf_temp'),
            'default_font' => 'xbriyaz',
            'orientation'  => 'L'
        ]);
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($synchronous->id, $generator::TYPE_CODE_128);
        $barcodeImage = base64_encode($barcode);
        $barcodeSrc = 'data:image/png;base64,'.$barcodeImage;
        $content = view('pdf.synchronous', [
            'barcode' => $barcodeSrc,
            'number' => '',
            'title' => '',
            'items' => $synchronous->SynchronousItems()->get()->map(function ($item) {
                return [
                    $item->vehicle->vin,
                    $item->vehicle->carModel->title,
                    $item->vehicle->makeCode->title,
                    $item->vehicle->color_code,
                    $item->created_at
                ];
            })->all()
        ])->render();
        $mpdf->WriteHTML($content);
        //$data = $mpdf->Output('synchronous.pdf', 'I');
        $data = $mpdf->Output(storage_path('app/synchronous.pdf'), 'F');

    }
}
