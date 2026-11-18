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

        $this->printer->sendData($ip, $this->renderTspl($engineNumber));
    }

    public function renderTspl(string $engineNumber): string
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
            imagecopy($label, $barcodeImage, intdiv(800 - $barcodeWidth, 2), 68, 0, 0, $barcodeWidth, 80);
            imagestring($text, $font, 0, 0, $engineNumber, $textBlack);
            $scale = min(3, 720 / $textWidth);
            $width = (int) floor($textWidth * $scale);
            $height = (int) floor($textHeight * $scale);
            imagecopyresized($label, $text, intdiv(800 - $width, 2), 8, 0, 0, $width, $height, $textWidth, $textHeight);

            // TSPL BITMAP uses MSB-first rows, with zero bits for black dots.
            // Rasterizing the text prevents user data from becoming printer commands.
            $raster = '';
            for ($y = 0; $y < 160; $y++) {
                for ($x = 0; $x < 800; $x += 8) {
                    $byte = 255;
                    for ($bit = 0; $bit < 8; $bit++) {
                        if (imagecolorat($label, $x + $bit, $y) === $black) {
                            $byte &= ~(1 << (7 - $bit));
                        }
                    }
                    $raster .= chr($byte);
                }
            }

            return "SIZE 100 mm,20 mm\r\nGAP 2 mm,0 mm\r\nDIRECTION 1\r\nCLS\r\n"
                .'BITMAP 0,0,100,160,0,'.$raster."\r\nPRINT 1,1\r\n";
        } finally {
            imagedestroy($barcodeImage);
            imagedestroy($label);
            imagedestroy($text);
        }
    }
}
