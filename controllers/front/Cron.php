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

use MpSoft\MpApiTyres\Catalog\DownloadCatalog;
use MpSoft\MpApiTyres\Const\Constants;
use MpSoft\MpApiTyres\Curl\GetCatalogApi;
use MpSoft\MpApiTyres\Import\ImportCatalog;
use MpSoft\MpApiTyres\Import\ReloadImages;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;

class MpApiTyresCronModuleFrontController extends ModuleFrontController
{
    use HumanTimingTrait;

    protected $id_lang;

    public function initContent()
    {
        $ajax = (int) Tools::getValue('ajax');
        $action = Tools::getValue('action');
        if ($ajax && !preg_match('/Action$/', $action)) {
            $action .= 'Action';
        }

        if (method_exists($this, $action) && $ajax) {
            $result = $this->$action();
            header('Content-Type: application/json');
            exit(json_encode($result));
        }

        if (method_exists($this, $action) && !$ajax) {
            $result = $this->$action();
            return $result;
        }
    }

    public function getCatalogAction()
    {
        $reset = (int) Tools::getValue('reset');

        if ($reset) {
            Configuration::updateValue(Constants::MPAPITYRES_CRON_DOWNLOAD_OFFSET, 0);
            Configuration::updateValue(Constants::MPAPITYRES_CRON_DOWNLOAD_UPDATED, 0);
            Configuration::updateValue(Constants::MPAPITYRES_CRON_DOWNLOAD_STATUS, "DONE");
            Configuration::updateValue(Constants::MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE, false);
        }

        $downloadCatalog = new DownloadCatalog();
        $response = $downloadCatalog->run();

        return $response;
    }

    public function createPrestashopCatalogAction()
    {
        $reset = (int) Tools::getValue('reset');

        if ($reset) {
            Configuration::updateValue(Constants::MPAPITYRES_CRON_IMPORT_STATUS, "DONE");
            Configuration::updateValue(Constants::MPAPITYRES_CRON_IMPORT_UPDATED_DATE, false);
        }

        $importCatalog = new ImportCatalog();
        $response = $importCatalog->run();
        return $response;
    }

    public function reloadImagesAction()
    {
        $reset = (int) Tools::getValue('reset');

        if ($reset) {
            Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_OFFSET, 0);
            Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED, 0);
            Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_STATUS, "RESET");
            Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED_DATE, false);
            Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_DELETED_IMAGES, "RESET");
        }

        $reloadImages = new ReloadImages();
        $response = $reloadImages->run();
        return $response;
    }

    public function getCatalogByStep($offset = 0, $limit = 3000, $minStock = 4)
    {
        $start = microtime(true);

        $result = $this->getApiCatalog($offset, $limit, $minStock);
        $stop = microtime(true);
        $time = $stop - $start;

        return [
            'offset' => (int) $result['count'],
            'updated' => (int) ($result['updated'] ?? 0),
            'time' => $this->getHumanTiming($time),
            'elapsed' => $time,
        ];
    }

    public function getApiCatalog($offset, $limit, $minStock)
    {
        $host = Configuration::get(Constants::MPAPITYRES_HOST_TYRES);
        $token = Configuration::get(Constants::MPAPITYRES_TOKEN_TYRES);
        $timeout = Configuration::get(Constants::MPAPITYRES_CRON_TIMEOUT);
        $endpoint = "/search";

        $getCatalogApi = new GetCatalogApi($host, $endpoint, $token, $timeout);
        $result = $getCatalogApi->run($offset, $limit, $minStock);

        return $result;
    }

    public function importCatalog()
    {
        $start = microtime(true);



        $stop = microtime(true);
        $time = $stop - $start;

        return [
            'time' => $this->getHumanTiming($time),
            'elapsed' => $time,
        ];
    }

    public function deleteProductsAction()
    {
        $MAX_TIMEOUT = 60; //secondi
        $id_lang = (int) Configuration::get("PS_LANG_DEFAULT");
        $pfx = _DB_PREFIX_;
        $start = microtime(true);
        $category_default = pSQL(Configuration::get('MPAPITYRES_CATEGORY_NAME'));

        $id_category_default = (int) Db::getInstance()->getValue("
            SELECT
                id_category
            FROM 
                {$pfx}category_lang
            WHERE
                name = '{$category_default}'
                AND
                id_lang = {$id_lang}
        ");

        $id_category_default = 2;

        if (!$id_category_default) {
            $id_category_default = (int) Configuration::get("_PS_CATEGORY_DEFAULT_");
        }

        $query = "
            SELECT
                id_product
            FROM
                {$pfx}product
            WHERE
                id_category_default = {$id_category_default}
            ORDER BY
                id_product ASC
            LIMIT
                1000
        ";

        $products = Db::getInstance()->executeS($query);
        $break = false;
        $deleted = 0;

        if (!$products) {
            return [
                'metodo' => 'DeleteProductsAction',
                'status' => 'DONE',
                'offset' => 0,
                'deleted' => $deleted,
                'message' => "Nessun prodotto trovato.",
                'elapsed' => 0,
                'time' => '0',
            ];
        }

        foreach ($products as $product) {
            //Calcolo il tempo trascorso dall'inizio della procedura ad adesso in secondi
            $elapsed_time = microtime(true) - $start; //secondi
            $human_timing = $this->getHumanTiming($elapsed_time);

            //COntrolla se il tempo trascorso è maggiore di $MAX_TIMEOUT
            if ($elapsed_time > $MAX_TIMEOUT) {
                return [
                    'metodo' => 'DeleteProductsAction',
                    'status' => 'DELETING',
                    'offset' => 0,
                    'deleted' => $deleted,
                    'message' => "Timeout raggiunto. Rilanciare il Cron.",
                    'elapsed' => $elapsed_time,
                    'time' => $human_timing,
                ];
            }

            $object = new Product($product['id_product']);
            $object->delete();
            $deleted++;
        }

        $stop = microtime(true);
        $elapsed = $stop - $start;


        return [
            'time' => $this->getHumanTiming($elapsed),
            'elapsed' => $elapsed,
            'products' => count($products),
            'deleteted' => $deleted,
            'break' => $break,
            'status' => 'DELETING',
        ];
    }
}