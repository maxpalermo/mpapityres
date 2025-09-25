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


/*
    Controller che serve per lo scaricamento del catalogo via API.
    Viene richiamato dal bottone btn-fetch-tyres-api nella sezione IMPORTA API
    route URL: mpapityres_admin_download_catalog_api
    script: downloadCatalog.js
*/


namespace MpSoft\MpApiTyres\Controllers;

use MpSoft\MpApiTyres\Handlers\FetchTyresHandler;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class DownloadCatalogApiController extends FrameworkBundleAdminController
{
    use HumanTimingTrait;

    /** @var \Doctrine\DBAL\Connection $connection */
    private $connection;

    /** @var int $updated */
    private $updated = 0;

    /** @var array $errors */
    private $errors = [];

    /** @var string $pfx */
    private $pfx;

    public function index(Request $request)
    {
        $timeStart = microtime(true);
        $elapsed = 0;
        $action = $request->request->get('action') ?? null;

        if ($action) {
            if (method_exists($this, $action)) {
                $this->connection = $this->get('doctrine.dbal.default_connection');
                $this->pfx = _DB_PREFIX_;
                $this->updated = 0;
                $params = $request->request->all();

                $response = $this->$action($params);
            } else {
                $elapsed = microtime(true) - $timeStart;
                return $this->json([
                    'success' => false,
                    'response' => [],
                    'errors' => ["Action {$action} non valida"],
                    'elapsed' => $elapsed,
                    'time' => $this->getHumanTiming($elapsed),
                ]);
            }

            $elapsed = microtime(true) - $timeStart;
            return $this->json([
                'success' => true,
                'action' => $action,
                'response' => $response ?? [],
                'errors' => $this->errors,
                'elapsed' => $elapsed,
                'time' => $this->getHumanTiming($elapsed),
            ]);
        }
    }

    private function truncate()
    {
        $query = "
            TRUNCATE TABLE {$this->pfx}product_tyre_download
        ";
        $statement = $this->connection->prepare($query);
        return $statement->executeStatement();
    }

    private function downloadCatalog($params)
    {
        $term = $params['term'] ?? "%";
        $offset = $params['offset'] ?? 0;
        $limit = $params['limit'] ?? 3000;
        $minStock = $params['minStock'] ?? 4;
        $this->updated = 0;

        $fetchTyresHandler = new FetchTyresHandler($term, $offset, $limit, $minStock);

        $response = $fetchTyresHandler->getTyresResult();

        if (isset($response['error'])) {
            return [
                'offset' => 0,
                'count' => 0,
                'error' => $response['error'],
            ];
        }

        if ($response['count'] == 0) {
            $last50 = $fetchTyresHandler->getLast50();
        } else {
            //Salvo i prodotti nella tabella

            $query = $this->getInsertQuery();
            $rows = $response['rows'] ?? [];

            foreach ($rows as $tyre) {
                $this->insertRow($query, $tyre);
            }

        }

        return [
            'offset' => $response['offset'],
            'count' => $response['count'],
            'last50' => $last50 ?? [],
            'updated' => $this->updated,
        ];
    }

    private function getInsertQuery()
    {
        $query = "
            INSERT IGNORE INTO
                {$this->pfx}product_tyre_download
                (id_t24, matchcode, content, date_add)
            VALUES
                (
                    :id_t24,
                    :matchcode,
                    :content,
                    :date_add
                ) 
        ";

        return $query;
    }

    private function insertRow($query, $tyre)
    {
        $values = [
            'id_t24' => $tyre['idT24'],
            'matchcode' => $tyre['matchcode'],
            'content' => json_encode($tyre),
            'date_add' => date('Y-m-d H:i:s'),
        ];

        try {
            $statement = $this->connection->prepare($query);
            $result = $statement->executeStatement($values);
            if ($result) {
                $this->updated++;
            }
        } catch (\Throwable $th) {
            $this->errors[] = "Errore {$th->getMessage()} durante il salvataggio del prodotto {$tyre['id_t24']} {$tyre['matchcode']}";
            $result = false;
        }

        return $result;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}