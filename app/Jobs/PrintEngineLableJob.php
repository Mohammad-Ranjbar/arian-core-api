<?php

namespace App\Jobs;

use App\Services\RawPrinterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Exceptions\UnknownTypeException;

class PrintEngineLableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(
        protected readonly string $engineNumber,
        protected readonly string $printerAddress
    ) {
        $this->onQueue('printing');
    }

    /**
     * @throws MpdfException
     * @throws UnknownTypeException
     */
    public function handle(): void
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [100, 20],
            'tempDir' => storage_path('app/mpdf_temp'),
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $generator = new BarcodeGeneratorSVG;
        $mpdf->imageVars['engineBarcode'] = $generator->getBarcode(
            $this->engineNumber,
            $generator::TYPE_CODE_128,
        );
        $mpdf->AddPage();
        $mpdf->Image('var:engineBarcode', 18, 3, 64, 9);

        $mpdf->SetFont('dejavusans', '', 12);
        $textWidth = $mpdf->GetStringWidth($this->engineNumber);
        if ($textWidth > 90) {
            $mpdf->SetFontSize(12 * 90 / $textWidth);
        }

        $mpdf->SetXY(0, 13);
        $mpdf->Cell(100, 5, $this->engineNumber, 0, 0, 'C');

        $path = storage_path('engine-lable-'.Str::uuid().'.pdf');

        try {
            $mpdf->Output($path, 'F');
            app(RawPrinterService::class)->sendFile($this->printerAddress, $path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
