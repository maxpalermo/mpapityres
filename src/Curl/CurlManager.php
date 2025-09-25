<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpApiTyres\Curl;

class CurlManager
{
    protected $endpoint;
    protected $token;
    protected $host;
    protected $method;
    protected $timeout;

    public function __construct($host, $endpoint, $token, $timeout = 30, $method)
    {
        if (preg_match('/\/\$/', $host)) {
            $host = substr($host, 0, -1);
        }
        if (!preg_match('/^https:\/\//', $host)) {
            $host = "https://{$host}";
        }
        if (!preg_match('/^\//', $endpoint)) {
            $endpoint = "/{$endpoint}";
        }

        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->host = $host;
        $this->method = $method;
        $this->timeout = $timeout;
    }

    /**
     * Summary of curlExec
     * @param mixed $header
     * @param mixed $body
     * @throws \Exception
     * @return array
     */
    public function curlExec($header = [], $body = []): array
    {
        $headerCurl = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-AUTH-TOKEN: ' . $this->token,
        ];

        if ($header) {
            $headerCurl = array_merge($headerCurl, $header);
        }

        $ch = curl_init();
        $endpoint = $this->host . $this->endpoint;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerCurl);

        switch ($this->method) {
            case "GET": {
                $additionalRequestParams = $body['additionalRequestParams'] ?? '';
                if ($additionalRequestParams) {
                    unset($body['additionalRequestParams']);
                    $query = http_build_query($body);
                    $query .= "&{$additionalRequestParams}";
                } else {
                    $query = http_build_query($body);
                }

                $endpoint .= "?{$query}";
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                break;
            }
            case "POST": {
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                break;
            }
            default: {
                throw new \Exception('Metodo non supportato: ' . $this->method);
            }
        }

        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
        } catch (\Throwable $th) {
            throw $th;
        }

        curl_close($ch);

        if ($response === false) {
            throw new \Exception('Errore CURL: ' . curl_error($ch));
        }

        if ($httpCode >= 400) {
            throw new \Exception(
                "
                    Errore API HTTP: {$httpCode}
                    Errore: {$error}
                    Codice errore: {$errno}
                    RISPOSTA: {$response}
                "
            );
        }

        return json_decode($response, true);
    }

    protected function getFilters()
    {
        $filename = _PS_MODULE_DIR_ . 'mpapityres' . '/views/assets/json/api_filters.json';
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            try {
                $data = json_decode($content, true);
            } catch (\Throwable $th) {
                $data = [];
            }
        }

        if ($data) {
            $dataFilter = "";
            foreach ($data as $key => $filter) {
                if (!is_array($filter)) {
                    $filter = [$filter];
                }
                $filterKey = str_replace("filter-", "", $key);
                if (in_array('all', $filter)) {
                    continue;
                }
                switch ($key) {
                    case 'filter-0':
                        $name = 'tyretype';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-1':
                        $name = 'manufacturer';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-2':
                        $name = 'manufacturer_category';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-4':
                        $name = 'speed';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-5':
                        $name = 'property';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-6':
                        $name = 'express_delivery';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                }
                foreach ($filter as $filterValue) {
                    $dataFilter .= "filter[{$filterKey}][valueList][]={$filterValue}&";
                }
                $dataFilter .= "filter[{$filterKey}][multipleChoice]={$multipleChoice}&";
            }
            $dataFilter = trim($dataFilter, '&');
        } else {
            $dataFilter = "";
        }

        return $dataFilter;
    }
}