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

namespace MpSoft\MpApiTyres\API;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class GuzzleApi
{
    protected $endpoint;
    protected $token;
    protected $host;
    protected $method;
    protected $timeout;

    public function __construct($timeout = 30)
    {
        $token = \Configuration::get('MPAPITYRES_API_TOKEN');

        $this->endpoint = 'tyre24.alzura.com/it/it/rest/V14/tyres';
        $this->token = $token;
        $this->timeout = $timeout;
    }

    public static function getProduct($tyre_id)
    {
        $guzzle = new self();
        return $guzzle->SEARCH($tyre_id);
    }

    public function SEARCH($tyre_id, $limit = 1, $offset = 0)
    {
        $this->host = 'tyre24.alzura.com/it/it/rest/V14';
        $this->endpoint = "/tyres/search?search=ID{$tyre_id}&limit={$limit}&offset={$offset}";

        $client = new Client([
            'base_uri' => $this->host,
            'timeout' => (float) $this->timeout,
            'verify' => true,
            'allow_redirects' => true,
            'http_errors' => false,  // gestiamo manualmente gli errori HTTP per coerenza con curlExec
        ]);

        $options = [
            'headers' => [
                'Cache-Control' => 'no-cache',
                // 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
                'Connection' => 'keep-alive',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-AUTH-TOKEN' => $this->token,
            ]
        ];

        $method = 'GET';
        $uri = $this->host . $this->endpoint;

        try {
            $response = $client->request($method, $uri, $options);
            $status = $response->getStatusCode();
            $contents = (string) $response->getBody();
        } catch (GuzzleException $e) {
            throw new \Exception('Errore Guzzle: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if ($status >= 400) {
            throw new \Exception(
                "Errore API HTTP: {$status}\nErrore: richiesta fallita con Guzzle\nRISPOSTA: {$contents}\n"
            );
        }

        $decoded = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Errore nel decoding JSON: ' . json_last_error_msg() . '\nRisposta: ' . $contents);
        }

        return $decoded;
    }

    public static function downloadImage($url)
    {
        $image = file_get_contents($url);
        if ($image) {
            return $image;
        }

        return false;
    }
}
