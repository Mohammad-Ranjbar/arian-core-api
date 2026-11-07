<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Picqer\Barcode\BarcodeGeneratorPNG;
use RuntimeException;

class EngineNumberLabelPrinter
{
    public function __construct(private readonly RawPrinterService $printer) {}

    public function print(string $engineNumber): void
    {
        $ip = config('printers.engine_label.ip');

        if (! is_string($ip) || trim($ip) === '') {
            throw new RuntimeException('آدرس چاپگر لیبل موتور در printers.engine_label.ip تنظیم نشده یا معتبر نیست؛ ENGINE_LABEL_PRINTER_IP و کش تنظیمات را بررسی کنید.');
        }

        $pdf = $this->renderPdf($engineNumber);
        $path = tempnam(storage_path('app'), 'engine-number-label-');

        if ($path === false) {
            throw new RuntimeException('ایجاد فایل موقت لیبل امکان‌پذیر نیست.');
        }

        try {
            if (file_put_contents($path, $pdf) !== strlen($pdf)) {
                throw new RuntimeException('ذخیره فایل لیبل امکان‌پذیر نیست.');
            }

            $this->printer->sendFile($ip, $path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function renderPdf(string $engineNumber): string
    {
        if (trim($engineNumber) === '' || ! preg_match('/\A[\x20-\x7E]+\z/', $engineNumber)) {
            throw ValidationException::withMessages([
                'engine_no' => 'شماره موتور باید شامل حروف لاتین، اعداد یا علائم قابل چاپ باشد.',
            ]);
        }

        $generator = new BarcodeGeneratorPNG;
        $barcode = $generator->getBarcode($engineNumber, $generator::TYPE_CODE_128, 2, 80);
        // Each Code 128 module is 0.25 mm; retain at least 5 mm of white space on each side.
        $barcodeWidth = getimagesizefromstring($barcode)[0] / 2 * 0.25;

        if ($barcodeWidth > 90) {
            throw ValidationException::withMessages([
                'engine_no' => 'شماره موتور برای بارکد این لیبل بیش از حد طولانی است.',
            ]);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [100, 20],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font' => 'dejavusansmono',
            'tempDir' => storage_path('app/mpdf_temp'),
        ]);
        $mpdf->SetAutoPageBreak(false);
        $mpdf->AddPage();
        $mpdf->Image('data:image/png;base64,'.base64_encode($barcode), (100 - $barcodeWidth) / 2, 2, $barcodeWidth, 10, 'png');
        $mpdf->SetFont('dejavusansmono', '', 12);
        $textWidth = $mpdf->GetStringWidth($engineNumber);
        if ($textWidth > 90) {
            $mpdf->SetFontSize(12 * 90 / $textWidth);
        }
        $mpdf->SetXY(5, 13);
        $mpdf->Cell(90, 5, $engineNumber, 0, 0, 'C');

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
