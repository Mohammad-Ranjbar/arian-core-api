<?php

namespace App\Jobs;

use App\Models\Synchronous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mpdf\Mpdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class PrintSynchronousJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $id)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Synchronous $synchronous */
        $synchronous = Synchronous::query()->find($this->id);
        if ($synchronous->capacity !== 0) {
            return;
        }
        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A5',
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
            'number'  => $synchronous->getAttribute('number'),
            'title'   => $synchronous->synchronousType->title,
            'items'   => $synchronous->SynchronousItems()->get()->map(function ($item) {
                return [
                    $item->vehicle->vin,
                    $item->vehicle->carModel->title,
                    $item->vehicle->makeCode->title,
                    $item->vehicle->color_code,
                    jdate($item->created_at)->toDateString(),
                    jdate($item->created_at)->toTimeString()
                ];
            })->all()
        ])->render();
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($content);
        $path = storage_path('synchronous-'.$synchronous->id.'.pdf');
        $mpdf->Output($path, 'F');
        $socket = fsockopen($synchronous->synchronousType->printer_ip, 9100);
        $pdf = file_get_contents($path);
        fwrite($socket, $pdf);
        fclose($socket);
        unlink($path);
    }
}
