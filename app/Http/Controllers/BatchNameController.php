<?php

namespace App\Http\Controllers;

use App\Services\Erp\ErpOdataClient;
use Illuminate\Http\Request;

class BatchNameController extends Controller
{
    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {

    }
    public function __invoke(Request $request)
    {
        $url = config('erp.batch_name');
        $data = [];
        $content = $this->erpOdataClient->get($url);
        if (isset($content->value)) {
            foreach ($content->value as $row) {
                $data[] = $row;
            }
        }

        $result = array_map(function($item) {
            return [
                'id' => $item,
                'title' => $item
            ];
        }, $data);
        return response()->json($result);
    }
}
