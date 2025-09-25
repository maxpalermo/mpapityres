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

class FetchTyresHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $term;
    private $offset;
    private $limit;
    private $minStock;
    private $filters;
    private $token;
    private $host;
    private $endpoint;
    private $timeout;

    private $db;

    public function __construct($term = "%", $offset = 0, $limit = 3000, $minStock = 4, $filters = [])
    {
        parent::__construct();
        $this->term = $term;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->minStock = $minStock;
        $this->filters = $filters;
        $this->db = \Db::getInstance();

        $host = \Configuration::get("MPAPITYRES_API_URL");
        $endpoint = "/search";
        $token = \Configuration::get("MPAPITYRES_TOKEN");
        $timeout = \Configuration::get("MPAPITYRES_CURL_TIMEOUT");

        $this->token = $token;
        $this->host = $host;
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;

        $pfx = _DB_PREFIX_;
        if ($offset === 0) {
            $connection = $this->db;
            $connection->execute("TRUNCATE TABLE {$pfx}product_tyre");
            $connection->execute("TRUNCATE TABLE {$pfx}product_tyre_pricelist");
        }
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
     * Recupera l'elenco dei pneumatici via API applicando i filtri
     * @return array<{rows, offset, error}>
     */
    public function getTyresResult(): array
    {
        $result = $this->runWithFilters($this->term, $this->minStock, $this->offset, $this->limit);

        return $result;
    }

    /**
     * Esegue una ricerca con filtri avanzati
     * 
     * @param string $searchTerm Termine di ricerca
     * @param int $minStock Stock minimo
     * @param int $offset Offset per la paginazione
     * @param int $limit Limite risultati
     * @return array<{rows, offset, error}>
     */
    private function runWithFilters($searchTerm = "%", $minStock = 4, $offset = 0, $limit = 3000): array
    {
        $baseUrl = "tyre24.alzura.com/it/it/rest/V14/tyres/search";

        // Parametri base
        $params = [
            'search' => $searchTerm,
            'limit' => $limit,
            'offset' => $offset,
            'minStock' => $minStock
        ];
        $http_params = http_build_query($params);

        // Aggiungi i filtri se presenti
        $filters = $this->getFilters();
        if ($filters) {
            $http_params .= "&" . $filters;
        }

        //Inizializzo la classe che si occupa del file JSON
        $jsonCatalogHandler = new JsonCatalogHandler();
        if ($offset == 0) {
            $jsonCatalogHandler->delete('tyres');
            $jsonCatalogHandler->delete('manufacturers');
            $jsonCatalogHandler->delete('manufacturers_tyres');
            $jsonCatalogHandler->delete('price_list');
        }

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
            return [
                'rows' => [],
                'offset' => 0,
                'count' => 0,
                'error' => 'Errore durante la richiesta'
            ];
        }

        $tyreList = [];
        $count = 0;

        try {
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $tyreList = $response['tyreList'] ?? [];
            $count = count($tyreList);

            if (!$tyreList) {
                return [
                    'rows' => [],
                    'offset' => 0,
                    'count' => 0,
                    'error' => 'Nessun risultato'
                ];
            }
        } catch (\Throwable $th) {
            return [
                'rows' => [],
                'offset' => 0,
                'count' => 0,
                'error' => $th->getMessage()
            ];
        }

        //Restituisco l'array scaricato
        try {
            return [
                'rows' => $tyreList,
                'offset' => $offset,
                'count' => $count,
            ];
        } catch (\Throwable $th) {
            return [
                'rows' => [],
                'offset' => 0,
                'count' => 0,
                'error' => $th->getMessage()
            ];
        }
    }

    private function getFilters()
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

    /**
     * Imposta i filtri di ricerca
     * 
     * @return array
     */
    private function getFilter()
    {
        $filter = <<<FILTER
        [
            {
                "filterId": "5",
                "filterName": "property",
                "filterValueList": [
                    {
                        "description": "Pneumatici runflat",
                        "valueId": "runflat",
                        "selected": 1
                    },
                    {
                        "description": "Nascondi DOT>36 mesi",
                        "valueId": "hideDot",
                        "selected": 1
                    },
                    {
                        "description": "Nascondi modelli di fine serie",
                        "valueId": "hideDiscontinued",
                        "selected": 1
                    },
                    {
                        "description": "Nascondi DEMO",
                        "valueId": "hideDemo",
                        "selected": 1
                    },
                    {
                        "description": "Solo DEMO",
                        "valueId": "onlyDemo",
                        "selected": 0
                    }
                ]
            }
        ]
        FILTER;

        try {
            return json_decode($filter, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
            return [];
        }
    }

    public function getLast50()
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                *
            FROM
                {$pfx}product_tyre_download
            ORDER BY
                id_t24 DESC
            LIMIT
                50
            ";
        $statement = $connection->prepare($query);
        $result = $statement->executeQuery();
        $rows = $result->fetchAllAssociative();
        $return = [];

        foreach ($rows as $row) {
            $return[] = json_decode($row['content'], true);
        }

        return $return;
    }
}
