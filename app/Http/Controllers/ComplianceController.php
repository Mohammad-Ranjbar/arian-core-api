<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class ComplianceController extends Controller
{
    /**
     * @throws MpdfException
     * @throws Exception
     */
    public function generate(string $vin): string
    {
        $vehicle = Vehicle::query()->where('vin', $vin)->firstOrFail();
        $vin = $vehicle->getAttribute('vin');

        $bladeName =  match ( $vehicle->carModel->reference) {
            'DSPCO010DP' =>'SX5G',
            'KSPCO010CP' =>'1AA',
            'ESPSE010DP' =>'AP00F',
            'TN721-N60' =>'N721',
            'TN721-N85' =>'N721',
            'TN721-N90' =>'N721',
            'TN721-N120' =>'N721',

            default => false,
        };
        if (!$bladeName){
            return response()->json("cant find ane view for car model $vehicle->carModel->reference");
        }
        $html = view('pdf.compliance.'.$vehicle->carModel->reference, compact('vin'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => storage_path('app/mpdf_temp'),
            'useOTL' => true,

            'useKerning' => true,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'useSubstitutions' => true,

            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);

        $fileName = time().'-certificate.pdf';
        $mpdf->Output(storage_path('app/public/' . $fileName), 'I');

        return asset('storage/' . $fileName);
    }
}
