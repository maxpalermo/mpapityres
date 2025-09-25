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

namespace MpSoft\MpApiTyres\Handlers;

use MpSoft\MpApiTyres\Handlers\JsonDistributorsHandler;

class FetchDistributorListHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $itemId;
    private $token;
    private $host;
    private $endpoint;
    private $username;
    private $password;

    public function __construct($itemId)
    {
        parent::__construct();
        $this->itemId = $itemId;

        // Carico i paramatri dal file .env
        $env = $this->loadEnv();
        $host = $env['API_REST_HOST'];
        $endpoint = $env['API_REST_TYRES14_ENDPOINT'];
        $token = $env['API_REST_TYRES14_TOKEN'];
        $username = $env['USER_NAME'];
        $password = $env['USER_PASSWORD'];

        $this->token = $token;
        $this->host = $host;
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Carica le variabili dal file .env nella root del modulo
     */
    private function loadEnv()
    {
        $envPath = realpath(_PS_MODULE_DIR_ . '/mpapityres/.env');
        $env = [];
        if ($envPath && file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $env[trim($key)] = trim($val);
                }
            }
        }
        return $env;
    }

    /**
     * Esegue una ricerca con filtri avanzati
     * 
     * @param bool $returnArray Se true, restituisce un array invece di una stringa JSON
     * @param string $searchTerm Termine di ricerca
     * @param int $minStock Stock minimo
     * @param int $offset Offset per la paginazione
     * @param int $limit Limite risultati
     * @param array $filters Array di filtri nel formato JSON fornito
     * @return array<{rows, offset, error}>
     */
    public function fetchDistributorsList(): array
    {
        $baseUrl = "tyre24.alzura.com/it/it/rest/V14/tyres/distributorList";

        // Parametri base
        $params = [
            'itemID' => $this->itemId,
        ];
        $http_params = http_build_query($params);

        // Costruzione URL con parametri
        $url = "https://" . $baseUrl . "?" . $http_params;

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    "X-AUTH-TOKEN: {$this->token}",
                ],
            ]
        );

        try {
            $response = curl_exec($curl);
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }

        curl_close($curl);

        //Controllo se la richiesta ha avuto successo
        if ($response === false || $response === null || $response === '') {
            return ['error' => 'Errore durante la richiesta'];
        }

        $offset = 0;

        try {
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $distributorList = $response ?? [];
            foreach ($distributorList as $key => $distributor) {
                if (strtolower($distributor['countryCode']) === 'it') {
                    unset($distributorList[$key]);
                }
            }
            $offset = count($distributorList);

            if (!$distributorList) {
                return ['error' => 'Nessun risultato'];
            }
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }

        //Restituisco l'array scaricato
        try {
            $filteredList = $this->filter(array_values($distributorList));
            return [
                'result' => count($filteredList),
                'list' => json_encode($filteredList),
            ];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function filter($distributorList)
    {
        //Filtro l'elenco dei distributori scegliendo solo il listino prezzi ek_one
        foreach ($distributorList as $key => $distributor) {
            foreach ($distributor['priceList'] as $key2 => $item) {
                //if ($item['type'] !== 'ek_one' || $item['value'] == 0 || $item['min_order'] > 1) {
                if ($item['value'] == 0) {
                    unset($distributorList[$key]['priceList'][$key2]);
                }
            }
            if (count($distributorList[$key]['priceList']) == 0) {
                unset($distributorList[$key]);
            }
            //Ordino il listino prezzi dal più basso al più alto
            usort($distributorList[$key]['priceList'], fn($a, $b) => $a['value'] - $b['value']);
        }

        //Ordino i distributori dal listino prezzi più basso al più alto
        usort($distributorList, fn($a, $b) => $a['priceList'][0]['value'] - $b['priceList'][0]['value']);

        return $distributorList;
    }

    public function getCheapestDistributor($distributorList, &$price)
    {
        $distributorCheapestPrice = [];
        $price = 0;
        foreach ($distributorList as $key => $distributor) {
            foreach ($distributor['priceList'] as $key2 => $item) {
                if ($item['type'] !== 'ek_one' || $item['value'] == 0 || $item['min_order'] > 1) {
                    continue;
                }

                $current_price = $item['value'];
                if ($price == 0 || $current_price < $price) {
                    $distributorCheapestPrice = $distributor;
                    $price = $current_price;
                }
            }
        }

        return $distributorCheapestPrice;
    }

    public function getCheapestPrice($distributorList)
    {
        $price = 0;
        $cheapestDistributor = $this->getCheapestDistributor($distributorList, $price);

        foreach ($cheapestDistributor['priceList'] as $key => $item) {
            if ($item['value'] != $price) {
                unset($cheapestDistributor['priceList'][$key]);
            }
        }

        return $cheapestDistributor;
    }


    public function write($distributorList, $idT24)
    {
        //TODO: Salva nella tabella distributori
    }
}
