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
            'TN721-N60', 'TN721-N85', 'TN721-N90', 'TN721-N120','JSCLT07LAD' =>'N721',
            'LORRY HCV-1402' => 'X5000L',
            'DUMPER HCV-1402','SSCHT022MD' => 'X5000D',
            'SHACMAN-X5000-1402','SSCTR01TAD','SHACMAN-X5000-1403','SSCHT012AD','SSCTR02TAD','SSCHT042MD' => 'X5000',
            default => false,
        };
        if (!$bladeName){
            $carModelTitle = $vehicle->carModel->reference;
            return response()->json("cant find ane view for car model $carModelTitle");
        }
        $bladeName = $vehicle->carModel->reference;
        if (in_array($vehicle->carModel->reference, ['SHACMAN-X5000-1402','SSCTR01TAD','SHACMAN-X5000-1403','SSCHT012AD','SSCTR02TAD','SSCHT042MD'])){
            $bladeName = 'X5000';
        }

        if (in_array($vehicle->carModel->reference, ['JSCLT07LAD'])){
            $bladeName = 'TN721-N120';
        }


        $html = view('pdf.compliance.'.$bladeName, compact('vin'))->render();

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
