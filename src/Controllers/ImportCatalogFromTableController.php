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
    Controller che serve per lo inserire il catalogo scaricato da Tyre nel database.
    Viene richiamato dal bottone btn-create-prestashop-catalog nella sezione IMPORTA API
    route URL: mpapityres_admin_import_catalog_from_table
    script: importCatalog.js
*/

namespace MpSoft\MpApiTyres\Controllers;

use MpSoft\MpApiTyres\Catalog\UpdateCatalog;
use MpSoft\MpApiTyres\Handlers\FetchTyresHandler;
use MpSoft\MpApiTyres\Helpers\ParseTyreCsvHelper;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class ImportCatalogFromTableController extends FrameworkBundleAdminController
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
        $result = $this->doAction($request);
        if ($result) {
            return $this->json($result);
        }

        try {
            $html = $this->renderView(
                '@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/importAPI.html.twig',
                [
                    'last50Tyres' => json_encode($this->getLast50Tyres()),
                    'manufacturers' => $this->getManufacturers(),
                    'totalManufacturers' => $this->getTotalManufacturers(),
                    'totalTyres' => $this->getTotalTyres(),
                    'totalSuppliers' => $this->getTotalSuppliers(),
                    'filters' => $this->getFilters(),
                    'taxRulesGroups' => $this->getTaxRulesGroups(),
                    'categoryName' => $this->getCategoryName(),
                    'idTaxRulesGroup' => $this->getIdTaxRulesGroup(),
                ]
            );
        } catch (\Throwable $th) {
            return $this->json([
                'error' => $th->getMessage(),
            ]);
        }

        return $this->json($html);
    }

    private function doAction(Request $request)
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
                return [
                    'success' => false,
                    'response' => [],
                    'errors' => ["Action {$action} non valida"],
                    'elapsed' => $elapsed,
                    'time' => $this->getHumanTiming($elapsed),
                ];
            }

            $elapsed = microtime(true) - $timeStart;
            return [
                'success' => true,
                'action' => $action,
                'response' => $response ?? [],
                'errors' => $this->errors,
                'elapsed' => $elapsed,
                'time' => $this->getHumanTiming($elapsed),
            ];
        }
    }

    private function synchronizeCatalog()
    {
        $startTime = microtime(true);
        $query = "
            SELECT
                id_product
            FROM
                {$this->pfx}product
            WHERE id_product NOT IN (SELECT id_t24 FROM {$this->pfx}product_tyre_download)
        ";

        $statement = $this->connection->prepare($query);
        $result = $statement->executeQuery();
        $fetch = $result->fetchAllAssociative();
        if ($fetch && !empty($fetch)) {
            $ids = array_column($fetch, 'id_product');
            $deactivateProducts = "
                UPDATE {$this->pfx}product
                SET active = 0
                WHERE id_product IN (" . implode(',', $ids) . ")
            ";
            $this->connection->prepare($deactivateProducts)->executeStatement();

            $deactivateProductShop = "
                UPDATE {$this->pfx}product_shop
                SET active = 0
                WHERE id_product IN (" . implode(',', $ids) . ")
            ";
            $this->connection->prepare($deactivateProductShop)->executeStatement();
        }

        $elapsed = microtime(true) - $startTime;

        return $this->json([
            'success' => true,
            'elapsed' => $elapsed,
            'time' => $this->getHumanTiming($elapsed),
        ]);
    }

    private function getTaxRulesGroups()
    {
        $id_lang = \Context::getContext()->language->id;
        return \TaxRulesGroup::getTaxRulesGroups($id_lang);
    }

    private function getTotalManufacturers()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}manufacturer";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalProducts()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}product";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalSuppliers()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}supplier";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalTyres()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}product_tyre";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getLast50Tyres()
    {
        $fetchTyresHandler = new FetchTyresHandler();
        $data = $fetchTyresHandler->getLast50();

        return $data;
    }

    private function getManufacturers()
    {
        return \Manufacturer::getManufacturers();
    }

    private function updateDistributor($distributor)
    {
        $model = new \MpSoft\MpApiTyres\Models\ProductTyrePriceListModel($distributor['id_t24']);

        $model->id_t24 = $distributor['id_t24'];
        $model->id_distributor = $distributor['id_distributor'];
        $model->name = $distributor['name'];
        $model->country = $distributor['country'];
        $model->country_code = $distributor['country_code'];
        $model->type = $distributor['type'];
        $model->min_order = $distributor['min_order'];
        $model->price_unit = $distributor['price_unit'];
        $model->delivery_time = $distributor['delivery_time'];
        $model->stock = $distributor['stock'];
        $model->active = $distributor['active'];
        $model->date_add = date('Y-m-d H:i:s');
        $model->date_upd = date('Y-m-d H:i:s');

        return $model->save();
    }

    private function saveApiSettings($params)
    {
        $category_name = $params['category'];
        $id_tax_rules_group = $params['id_tax_rules_group'];

        \Configuration::updateValue('MPAPITYRES_CATEGORY_NAME', $category_name);
        \Configuration::updateValue('MPAPITYRES_ID_TAX_RULES_GROUP', $id_tax_rules_group);

        return true;
    }

    private function saveApiFilters($params)
    {
        $filter0 = $params['filter-0'];
        $filter1 = $params['filter-1'];
        $filter2 = $params['filter-2'];
        $filter4 = $params['filter-4'];
        $filter5 = $params['filter-5'];
        $filter6 = $params['filter-6'];

        $filename = _PS_MODULE_DIR_ . 'mpapityres' . '/views/assets/json/api_filters.json';
        $data = [
            'filter-0' => explode(',', $filter0),
            'filter-1' => explode(',', $filter1),
            'filter-2' => explode(',', $filter2),
            'filter-4' => explode(',', $filter4),
            'filter-5' => explode(',', $filter5),
            'filter-6' => explode(',', $filter6),
        ];
        $result = file_put_contents($filename, json_encode($data));

        return [
            'success' => $result,
        ];
    }

    public function getFiltersJson()
    {
        return [
            'filters' => $this->getFilters(),
            'sorters' => $this->getSorters(),
        ];
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

        return $data;
    }

    protected function getSorters()
    {
        return file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/sorters.json');
    }

    private function getCategoryName()
    {
        return \Configuration::get('MPAPITYRES_CATEGORY_NAME');
    }

    private function getIdTaxRulesGroup()
    {
        return \Configuration::get('MPAPITYRES_ID_TAX_RULES_GROUP');
    }

    private function importCatalog($params)
    {
        $start = microtime(true);
        $offset = $params['offset'] ?? 0;
        $limit = $params['limit'] ?? 100;
        $updated = 0;

        $connection = $this->get('doctrine.dbal.default_connection');
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                id_t24,
                matchcode,
                content,
                date_add
            FROM
                {$pfx}product_tyre_download
            LIMIT
                $offset, $limit
        ";

        $statement = $connection->prepare($query);
        $result = $statement->executeQuery();
        $fetch = $result->fetchAllAssociative();
        $updateCatalog = new UpdateCatalog();
        if (!empty($fetch)) {
            foreach ($fetch as $row) {
                $row = json_decode($row['content'], true);
                $updateProduct = $updateCatalog->updateProduct($row);
                if ($updateProduct['success']) {
                    $updated++;
                }
            }
        } else {
            return [
                'offset' => 0,
                'updated' => 0,
            ];
        }

        $elapsed = microtime(true) - $start;

        return [
            'offset' => $offset + $limit,
            'updated' => $updated,
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
        ];
    }
}
