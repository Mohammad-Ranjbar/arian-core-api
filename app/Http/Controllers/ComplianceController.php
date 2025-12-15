<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
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
     */
    public function generate(string $vin): string
    {
        $vehicle = Vehicle::query()->where('vin', $vin)->firstOrFail();
        $data = [
            'manufacturer' => 'مجتمع صنعتی آرین پارس موتور',
            'type' => $vehicle->carModel->title,
            'brand' => $vehicle->makeCode->title,
            'group' => 'M1',
            'vin' => $vin,
            'approval' => 'IRN*6924*0297',
            'approval_date' => '۱۴۰۰/۱۲/۲۱',
            'date' => now()->format('Y/m/d'),
        ];

        $html = view('pdf.compliance', $data)->render();

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
