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

class GetCatalogApi extends CurlManager
{
    private $db;
    private $updated;
    private $errors = [];

    public function __construct($host, $endpoint, $token, $timeout = 30)
    {
        parent::__construct($host, $endpoint, $token, $timeout, "GET");
        $this->db = \Db::getInstance();
        $this->updated = 0;
        $this->errors = [];
    }

    public function run($offset = 0, $limit = 3000, $minstock = 4)
    {
        $body = [
            'search' => "%",
            'offset' => $offset,
            'limit' => $limit,
            'minStock' => $minstock,
        ];

        return $this->downloadCatalog($body);
    }

    private function downloadCatalog($params)
    {
        $this->updated = 0;

        try {
            $filters = $this->getFilters();
            $params['additionalRequestParams'] = $filters;

            $response = $this->curlExec([], $params);
            file_put_contents(_PS_MODULE_DIR_ . 'mpapityres' . '/views/assets/json/api_response.json', json_encode($response));
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog($th->getMessage(), 3, $th->getCode(), 'GetCatalogCurlExec');
            return [
                'method' => 'curlExec',
                'offset' => 0,
                'count' => 0,
                'error' => $th->getMessage(),
                'updated' => 0,
            ];
        }

        if (isset($response['error'])) {
            \PrestaShopLogger::addLog($response['error'], 3, 999, 'GetCatalogResponseError');
            return [
                'method' => "response['error']",
                'offset' => 0,
                'count' => 0,
                'error' => $response['error'],
                'updated' => 0,
            ];
        }

        //Salvo i prodotti nella tabella
        $rows = $response['tyreList'] ?? [];
        $total_rows = count($rows) ?? 0;

        \PrestaShopLogger::addLog("Trovati {$total_rows} prodotti", 1, 0, 'GetCatalogDownloadCatalog');

        foreach ($rows as $tyre) {
            if ($this->insertRow($tyre)) {
                $this->updated++;
            }
        }

        \PrestaShopLogger::addLog("Aggiornati {$this->updated} prodotti", 1, 0, 'GetCatalogDownloadCatalog');

        return [
            'method' => 'downloadCatalog',
            'offset' => $params['offset'],
            'count' => (int) $total_rows,
            'updated' => $this->updated,
            'tyreListMaxResult' => $response['tyreListMaxResult'] ?? 0,
        ];
    }

    private function insertRow($tyre)
    {
        $values = [
            'id_t24' => $tyre['idT24'],
            'matchcode' => $tyre['matchcode'],
            'content' => pSQL(json_encode($tyre)),
            'date_add' => date('Y-m-d H:i:s'),
        ];

        try {
            $result = $this->db->insert(
                'product_tyre',
                $values,
                true,
                false,
                \DbCore::REPLACE,
                true
            );
        } catch (\Throwable $th) {
            $this->errors[] = "Errore {$th->getMessage()} durante il salvataggio del prodotto {$tyre['idT24']} {$tyre['matchcode']}";
            $result = false;
        }

        return $result;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}