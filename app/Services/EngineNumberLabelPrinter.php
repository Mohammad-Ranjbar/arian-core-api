<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
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

        $data = $this->renderZpl($engineNumber);
        $path = tempnam(storage_path('app'), 'engine-number-label-');

        if ($path === false) {
            throw new RuntimeException('ایجاد فایل موقت لیبل امکان‌پذیر نیست.');
        }

        try {
            if (file_put_contents($path, $data) !== strlen($data)) {
                throw new RuntimeException('ذخیره فایل لیبل امکان‌پذیر نیست.');
            }

            $this->printer->sendFile($ip, $path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function renderZpl(string $engineNumber): string
    {
        if (trim($engineNumber) === '' || ! preg_match('/\A[\x20-\x7E]+\z/', $engineNumber)) {
            throw ValidationException::withMessages([
                'engine_no' => 'شماره موتور باید شامل حروف لاتین، اعداد یا علائم قابل چاپ باشد.',
            ]);
        }

        $generator = new BarcodeGeneratorPNG;
        $barcode = $generator->getBarcode($engineNumber, $generator::TYPE_CODE_128, 2, 80);
        $barcodeImage = imagecreatefromstring($barcode);
        $barcodeWidth = imagesx($barcodeImage);

        // GI-2408T: 8 dots/mm, so 100 x 20 mm is 800 x 160 dots.
        // Keep at least 5 mm (40 dots) of white space on both sides.
        if ($barcodeWidth > 720) {
            imagedestroy($barcodeImage);

            throw ValidationException::withMessages([
                'engine_no' => 'شماره موتور برای بارکد این لیبل بیش از حد طولانی است.',
            ]);
        }

        $label = imagecreate(800, 160);
        imagecolorallocate($label, 255, 255, 255);
        $black = imagecolorallocate($label, 0, 0, 0);
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($engineNumber);
        $textHeight = imagefontheight($font);
        $text = imagecreate($textWidth, $textHeight);
        imagecolorallocate($text, 255, 255, 255);
        $textBlack = imagecolorallocate($text, 0, 0, 0);

        try {
            imagecopy($label, $barcodeImage, intdiv(800 - $barcodeWidth, 2), 16, 0, 0, $barcodeWidth, 80);
            imagestring($text, $font, 0, 0, $engineNumber, $textBlack);
            $scale = min(2, 720 / $textWidth);
            $width = (int) floor($textWidth * $scale);
            $height = (int) floor($textHeight * $scale);
            imagecopyresized($label, $text, intdiv(800 - $width, 2), 104, 0, 0, $width, $height, $textWidth, $textHeight);

            // ZPL ^GFA uses MSB-first monochrome rows: one means a black dot.
            // Rasterize user text too, so quotes, ^ and ~ cannot become commands.
            $raster = '';
            for ($y = 0; $y < 160; $y++) {
                for ($x = 0; $x < 800; $x += 8) {
                    $byte = 0;
                    for ($bit = 0; $bit < 8; $bit++) {
                        if (imagecolorat($label, $x + $bit, $y) === $black) {
                            $byte |= 1 << (7 - $bit);
                        }
                    }
                    $raster .= chr($byte);
                }
            }

            return "^XA\r\n^PW800\r\n^LL160\r\n^LH0,0\r\n^LT0\r\n^LS0\r\n^PON\r\n^FWN\r\n^LRN\r\n"
                .'^FO0,0^GFA16000,16000,100,'.strtoupper(bin2hex($raster))."^FS\r\n^PQ1\r\n^XZ\r\n";
        } finally {
            imagedestroy($barcodeImage);
            imagedestroy($label);
            imagedestroy($text);
        }
    }
}
