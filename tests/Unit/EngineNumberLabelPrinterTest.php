<?php

namespace Tests\Unit;

use App\Services\EngineNumberLabelPrinter;
use App\Services\RawPrinterService;
use PHPUnit\Framework\TestCase;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EngineNumberLabelPrinterTest extends TestCase
{
    public function test_tspl_raster_contains_centered_text_above_the_exact_barcode(): void
    {
        $number = '00123ABC456789';
        $service = new EngineNumberLabelPrinter(new RawPrinterService);
        $tspl = $service->renderTspl($number);

        $header = "SIZE 100 mm,20 mm\r\nGAP 2 mm,0 mm\r\nDIRECTION 1\r\nCLS\r\nBITMAP 0,0,100,160,0,";
        $this->assertStringStartsWith($header, $tspl);
        $this->assertStringEndsWith("\r\nPRINT 1,1\r\n", $tspl);
        $raster = substr($tspl, strlen($header), 16000);
        $this->assertSame(16000, strlen($raster));
        $pixel = static fn (int $x, int $y): int => 1 - ((ord($raster[$y * 100 + intdiv($x, 8)]) >> (7 - $x % 8)) & 1);

        $generator = new BarcodeGeneratorPNG;
        $barcode = imagecreatefromstring($generator->getBarcode($number, $generator::TYPE_CODE_128, 2, 80));
        try {
            $left = intdiv(800 - imagesx($barcode), 2);
            // Compare a complete scan line, including every Code 128 module and quiet zone.
            for ($x = 0; $x < 800; $x++) {
                $expected = 0;
                if ($x >= $left && $x < $left + imagesx($barcode)) {
                    $color = imagecolorsforindex($barcode, imagecolorat($barcode, $x - $left, 40));
                    $expected = $color['alpha'] < 127 && $color['red'] === 0 ? 1 : 0;
                }
                $this->assertSame($expected, $pixel($x, 108), "Incorrect barcode dot at x={$x}");
            }
        } finally {
            imagedestroy($barcode);
        }

        $this->assertSame(str_repeat("\xff", 800), substr($raster, 0, 800));
        $this->assertNotSame(str_repeat("\xff", 4500), substr($raster, 800, 4500));
        for ($y = 0; $y < 160; $y++) {
            $row = substr($raster, $y * 100, 100);
            $this->assertSame(str_repeat("\xff", 5), substr($row, 0, 5));
            $this->assertSame(str_repeat("\xff", 5), substr($row, -5));
        }
    }

    public function test_printable_printer_commands_are_rendered_as_pixels_without_injecting_commands(): void
    {
        $service = new EngineNumberLabelPrinter(new RawPrinterService);
        $tspl = $service->renderTspl('A"^XZ~JA\\123');

        $this->assertStringNotContainsString('~JA', $tspl);
        $this->assertSame(1, substr_count($tspl, "\r\nPRINT 1,1\r\n"));
    }
}
