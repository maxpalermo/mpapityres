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

class FetchDistributorsHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $term;
    private $offset;
    private $limit;
    private $minStock;
    private $filters;
    private $token;
    private $host;
    private $endpoint;
    private $username;
    private $password;

    public function __construct($term, $offset, $limit, $minStock, $filters)
    {
        parent::__construct();
        $this->term = $term;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->minStock = $minStock;
        $this->filters = $filters;

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

        $pfx = _DB_PREFIX_;
        if ($offset === 0) {
            /*
            $connection = $this->connection;
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_pricelist");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_manufacturer");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_manufacturer_tyre");
            */
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
    public function getDistributors(): array
    {
        $result = $this->runWithFilters($this->term, $this->minStock, $this->offset, $this->limit);

        return $result;
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
    private function runWithFilters($searchTerm = "%", $minStock = 4, $offset = 0, $limit = 6500, $filters = []): array
    {
        $baseUrl = "tyre24.alzura.com/it/it/rest/V14/tyres/search";
        if (!$filters) {
            $filters = $this->getFilter();
        }

        // Parametri base
        $params = [
            'search' => $searchTerm,
            'limit' => $limit,
            'offset' => $offset,
            'minStock' => $minStock
        ];

        // Aggiungi i filtri se presenti
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $filterId = $filter['filterId'];
                $params["filter[{$filterId}][name]"] = $filter['filterName'];

                if (isset($filter['filterValueList']) && is_array($filter['filterValueList'])) {
                    $idx = 0;
                    foreach ($filter['filterValueList'] as $value) {
                        if (isset($value['valueId']) && (int) $value['selected'] == 1) {
                            $params["filter[{$filterId}][valueList][$idx]"] = $value['valueId'];
                            $idx++;
                        }
                    }
                }
            }
        }

        //Inizializzo la classe che si occupa del file JSON
        $jsonCatalogHandler = new JsonCatalogHandler();
        if ($offset == 0) {
            $jsonCatalogHandler->delete();
        }

        // Costruzione URL con parametri
        $url = "https://" . $baseUrl . "?" . http_build_query($params);

        // URL senza filtri
        $urlNoFilters = "https://" . $baseUrl . "?" . http_build_query([
            'search' => $searchTerm,
            'limit' => $limit,
            'offset' => $offset,
            'minStock' => $minStock
        ]);

        $url = $urlNoFilters;

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

        $tyreList = [];
        $offset = 0;

        try {
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $tyreList = $response['tyreList'] ?? [];
            $offset = count($tyreList);

            if (!$tyreList) {
                return ['error' => 'Nessun risultato'];
            }
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }


        //Salvo il file JSON
        $resultWrite = $jsonCatalogHandler->write($tyreList);
        if ($resultWrite === false) {
            return ['error' => 'Errore durante la scrittura del file JSON'];
        }

        //Restituisco l'array scaricato
        try {
            return [
                'rows' => json_encode([]),
                'offset' => $offset,
            ];
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
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
        $jsonCatalogHandler = new JsonCatalogHandler();
        return $jsonCatalogHandler->read(-50);
    }
}
