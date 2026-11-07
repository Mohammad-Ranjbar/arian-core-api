<?php

namespace Tests\Unit;

use App\Services\EngineNumberLabelPrinter;
use App\Services\RawPrinterService;
use PHPUnit\Framework\TestCase;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EngineNumberLabelPrinterTest extends TestCase
{
    public function test_zpl_raster_contains_the_exact_barcode_with_white_margins_and_text_below_it(): void
    {
        $number = '00123ABC456789';
        $service = new EngineNumberLabelPrinter(new RawPrinterService);
        $zpl = $service->renderZpl($number);

        $this->assertSame(1, preg_match('/\^GFA16000,16000,100,([0-9A-F]{32000})\^FS/', $zpl, $matches));
        $raster = hex2bin($matches[1]);
        $this->assertSame(16000, strlen($raster));
        $pixel = static fn (int $x, int $y): int => (ord($raster[$y * 100 + intdiv($x, 8)]) >> (7 - $x % 8)) & 1;

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
                $this->assertSame($expected, $pixel($x, 56), "Incorrect barcode dot at x={$x}");
            }
        } finally {
            imagedestroy($barcode);
        }

        $this->assertSame(str_repeat("\0", 1600), substr($raster, 0, 1600));
        $this->assertNotSame(str_repeat("\0", 4000), substr($raster, 10400, 4000));
        for ($y = 0; $y < 160; $y++) {
            $row = substr($raster, $y * 100, 100);
            $this->assertSame(str_repeat("\0", 5), substr($row, 0, 5));
            $this->assertSame(str_repeat("\0", 5), substr($row, -5));
        }
    }

    public function test_printable_printer_commands_are_rendered_as_pixels_without_injecting_commands(): void
    {
        $service = new EngineNumberLabelPrinter(new RawPrinterService);
        $zpl = $service->renderZpl('A"^XZ~JA\\123');

        $this->assertSame(1, substr_count($zpl, '^XA'));
        $this->assertSame(1, substr_count($zpl, '^XZ'));
        $this->assertSame(1, substr_count($zpl, '^PQ1'));
        $this->assertStringNotContainsString('~JA', $zpl);
        $this->assertMatchesRegularExpression('/\^GFA16000,16000,100,[0-9A-F]{32000}\^FS/', $zpl);
    }
}
