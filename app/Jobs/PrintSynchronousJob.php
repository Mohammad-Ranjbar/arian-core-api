<?php

namespace App\Jobs;

use App\Models\Synchronous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class PrintSynchronousJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $id)
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
        $data = $mpdf->Output('synchronous.pdf', 'I');
        Storage::put('synchronous.pdf',$data);
    }
}
