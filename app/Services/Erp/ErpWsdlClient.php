<?php

namespace App\Services\Erp;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ErpWsdlClient
{

    /**
     * @param $url
     * @param $method
     * @param array $params
     * @return array
     */
    public static function run($url, $method, array $params = []): array
    {
        $api = array_reverse(explode('/', parse_url($url)['path']))[0];

        $client = new Client();
        try {
            $request = new Request('POST', $url, [
                'Content-Type' => 'application/xml',
                "soapaction" => "\"urn:microsoft-dynamics-schemas/codeunit/$api:$method\""
            ], self::getBodyXML($api, $method, $params));

            $response = $client->sendAsync($request, [
                'auth' => config('erp.auth')
            ])->wait();
            logger($response->getBody()->getContents());
            $list = (array)json_decode(trim(strip_tags($response->getBody()->getContents())));

            if (empty($list)) {
                return [
                    'status' => 'error',
                    'message' => 'هیچ پاسخی از نویژن دریافت نشد'
                ];
            }

            $output = [];
            foreach ($list as $key => $value) {
                $output[strtolower($key)] = $value;
            }
            if (isset($output['status']) and in_array(strtolower($output['status']),
                    ['ok', 'done', 'success', '1', 'true'])) {
                $output['status'] = 'ok';
            }
            if (isset($output['status']) and in_array(strtolower($output['status']),
                    ['error', 'fail', '0', 'false', 'not'])) {
                $output['status'] = 'error';
            }

            return $output;

        } catch (Exception $exception) {
            return [
                'status' => 'error',

                'message' => strip_tags(preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3",

                    $exception->getResponse()->getBody()->getContents()))
            ];
        }
    }

    /**
     * @param $api
     * @param $method
     * @param $params
     * @return string
     */
    private static function getBodyXML($api, $method, $params): string
    {
        $string = '';
        foreach ($params as $key => $value) {
            $string .= "<$key>$value</$key>";
        }

        return
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <' . $method . ' xmlns="urn:microsoft-dynamics-schemas/codeunit/' . $api . '">
            ' . $string . '
        </' . $method . '>
    </Body>
</Envelope>';
    }
}
