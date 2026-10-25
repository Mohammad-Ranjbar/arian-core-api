<?php

namespace App\Jobs;

use App\Models\Synchronous;
use App\Services\RawPrinterService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\Exceptions\UnknownTypeException;

class PrintSynchronousJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $id)
    {
        $this->onQueue('printing');
    }

    /**
     * @throws MpdfException
     * @throws UnknownTypeException
     */
    public function handle(): void
    {
        /** @var Synchronous $synchronous */
        $synchronous = Synchronous::query()->findOrFail($this->id);
        $ip = $synchronous->getAttribute('synchronousType')->printer_ip;
        $type = $synchronous->getAttribute('synchronousType')->type;

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }
        if ($synchronous->getAttribute('capacity') !== 0) {
            return;
        }

        if ($type === 'engine') {
            $this->enginePrint($synchronous, $ip);
        }
        if ($type === 'other') {
            $this->otherPrint($synchronous, $ip);
        }

    }

    /**
     * @throws MpdfException
     * @throws UnknownTypeException
     */
    private function enginePrint(Synchronous $synchronous, $ip): void
    {

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [100, 50],
            'tempDir' => storage_path('app/mpdf_temp'),
            'default_font' => 'xbriyaz',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 5,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);
        $generator = new BarcodeGeneratorPNG;
        $item = $synchronous->SynchronousItems()->first();
        $barcode = $generator->getBarcode($item->vehicle->vin, $generator::TYPE_CODE_128, 1.6, 20);
        $barcodeImage = base64_encode($barcode);
        $barcodeSrc = 'data:image/png;base64,'.$barcodeImage;
        $content = view('pdf.synchronous-engine', [
            'barcode' => $barcodeSrc,
            'vin' => $item->vehicle->vin,
            'carModel' => $item->vehicle->carModel->title,
            'productItem' => $item->erpProductItem ? $item->erpProductItem->title.' '.$item->erpProductItem->reference : '---',
            'date' => jdate()->format('Y/m/d'),
            'time' => Carbon::now()->format('H:i'),
            'number' => $synchronous->getAttribute('number'),
        ])->render();
        $mpdf->WriteHTML($content);
        $path = storage_path('synchronous-'.$synchronous->getAttribute('id').'-'.Str::uuid().'.pdf');
        $mpdf->Output($path, 'F');
        $this->sendToPrinter($ip, $path);
    }

    /**
     * @throws MpdfException
     * @throws UnknownTypeException
     */
    private function otherPrint(Synchronous $synchronous, $ip): void
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('app/mpdf_temp'),
            'default_font' => 'xbriyaz',
            'orientation' => 'L',
        ]);
        $generator = new BarcodeGeneratorPNG;
        $barcode = $generator->getBarcode($synchronous->getAttribute('id'), $generator::TYPE_CODE_128);
        $barcodeImage = base64_encode($barcode);
        $barcodeSrc = 'data:image/png;base64,'.$barcodeImage;
        $content = view('pdf.synchronous-other', [
            'barcode' => $barcodeSrc,
            'number' => $synchronous->getAttribute('number'),
            'title' => $synchronous->getAttribute('synchronousType')->title,
            'items' => $synchronous->SynchronousItems()->get()->map(function ($item) {
                return [
                    $item->vehicle->vin,
                    $item->vehicle->getProductionType(),
                    $item->vehicle->carModel->title,
                    $item->erpProductItem?->title,
                    $item->vehicle->makeCode->title,
                    $item->vehicle->data['VariantCode'] ?? '',
                    $item->vehicle->grade ?? '-',
                    jdate($item->created_at)->toDateString(),
                    jdate($item->created_at)->toTimeString(),
                ];
            })->all(),
        ])->render();
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($content);
        $path = storage_path('synchronous-'.$synchronous->getAttribute('id').'-'.Str::uuid().'.pdf');
        $mpdf->Output($path, 'F');
        $this->sendToPrinter($ip, $path);
    }

    private function sendToPrinter(string $ip, string $path): void
    {
        try {
            app(RawPrinterService::class)->sendFile($ip, $path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
