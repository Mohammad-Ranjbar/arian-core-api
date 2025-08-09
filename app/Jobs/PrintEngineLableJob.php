<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\Exceptions\UnknownTypeException;

class PrintEngineLableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        protected readonly string $engineNumber,
        protected readonly string $vin,
        protected readonly string $printerAddress,
        protected readonly string $id
    )
    {
        //
    }


    /**
     * @throws MpdfException
     * @throws UnknownTypeException
     */
    public function handle():void
    {
        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A5',
            'tempDir'      => storage_path('app/mpdf_temp'),
            'default_font' => 'xbriyaz',
            'orientation'  => 'L'
        ]);
        $data['vin_barcode'] = '';
        $data['vin'] = '';
        $data['id'] = $this->id;
        $generator = new BarcodeGeneratorPNG();
        if (!empty($this->vin)){
            $vinBarcode = $generator->getBarcode($this->vin, $generator::TYPE_CODE_128);
            $barcodeImage = base64_encode($vinBarcode);
            $data['vin_barcode'] = 'data:image/png;base64,'.$barcodeImage;
            $data['vin'] = $this->vin;
        }


        $engineBarcode = $generator->getBarcode($this->engineNumber, $generator::TYPE_CODE_128);
        $barcodeImage = base64_encode($engineBarcode);
        $data['engine_barcode'] = 'data:image/png;base64,'.$barcodeImage;
        $data['engine'] = $this->engineNumber;

        $content = view('pdf.lable-engine', $data)->render();
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($content);
        $path = storage_path('engine-lable-'.$this->engineNumber.'.pdf');
        $mpdf->Output($path, 'F');
        $socket = fsockopen($this->printerAddress, 9100);
        $pdf = file_get_contents($path);
        fwrite($socket, $pdf);
        fclose($socket);
//        unlink($path);
    }
}
