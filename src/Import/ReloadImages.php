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

namespace MpSoft\MpApiTyres\Import;

use MpSoft\MpApiTyres\Catalog\UpdateCatalog;
use MpSoft\MpApiTyres\Const\Constants;
use MpSoft\MpApiTyres\Traits\DownloadImageFromUrlTrait;
use MpSoft\MpApiTyres\Traits\GetLastErrorTrait;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;

class ReloadImages
{
    use HumanTimingTrait;
    use DownloadImageFromUrlTrait;
    use GetLastErrorTrait;

    private $db;
    private $offset;
    private $limit;
    private $updated;
    private $status;
    private $time_limit;
    private $updated_date;
    private $deletedImages;
    private $time_between_updates;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->limit = 999999999;
        $this->time_limit = ((int) \Configuration::get(Constants::MPAPITYRES_CRON_TIMEOUT)) ?: 60; //secondi
        $this->time_between_updates = \Configuration::get(Constants::MPAPITYRES_CRON_TIME_BETWEEN_UPDATES) ?: 120;
        $this->readStatus();
    }

    protected function writeStatus()
    {
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_OFFSET, $this->offset);
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED, $this->updated);
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_STATUS, $this->status);
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED_DATE, $this->updated_date);
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_DELETED_IMAGES, $this->deletedImages);
    }

    protected function readStatus()
    {
        $this->offset = (int) \Configuration::get(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_OFFSET) ?: 0;
        $this->updated = (int) \Configuration::get(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED) ?: 0;
        $this->status = \Configuration::get(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_STATUS) ?: "FETCHING";
        $this->updated_date = \Configuration::get(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED_DATE) ?: false;
        $this->deletedImages = \Configuration::get(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_DELETED_IMAGES) ?: "DONE";
    }

    public function run()
    {
        $start = microtime(true);
        $elapsed = 0;
        $break = false;

        //Leggo il contenuto della tabella product
        $default_category = \Configuration::get('MPAPITYRES_CATEGORY_NAME');
        $id_default_category = $this->getIdCategoryByName($default_category);
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql->select('id_product')
            ->from('product')
            ->where('id_category_default = ' . $id_default_category)
            ->orderBy('id_product ASC');
        $products = $db->executeS($sql);
        $id_products = array_column($products, 'id_product');

        if (!$id_products) {
            return [
                'metodo' => 'ReloadImages',
                'status' => 'DONE',
                'offset' => 0,
                'updated' => 0,
                'httpCode' => 200,
                'contentType' => 'text/plain',
                'error' => 0,
                'error_message' => '',
                'last_updated_date' => $this->updated_date,
                'message' => "Nessun prodotto trovato.",
                'elapsed' => 0,
                'time' => '0',
            ];
        }

        $arrayUrlsTyre = [];
        $arrayUrlsNNG = [];
        foreach ($id_products as $id_product) {
            $urlTyre = $this->createTyreUrlOriginal($id_product);
            $arrayUrlsTyre[] = $urlTyre;
            $urlNNG = $this->createTyreUrl($id_product);
            $arrayUrlsNNG[] = $urlNNG;
            if (count($arrayUrlsTyre) == 500) {
                break;
            }
        }

        $imagesTyre = self::guzzleDownloadArray($arrayUrlsTyre);
        $imagesNng = self::guzzleDownloadArray($arrayUrlsNNG);

        $elapsed = microtime(true) - $start;
        $this->status = "DONE";
        $this->writeStatus();

        return [
            'metodo' => 'ReloadImages: downloadImages',
            'status' => $this->status,
            'offset' => $this->offset,
            'updated' => $this->updated,
            'httpCode' => 200,
            'contentType' => 'text/plain',
            'error' => 0,
            'error_message' => '',
            'message' => $this->status == "DONE" ? 'Operazione completata' : 'Operazione in corso',
            'time' => $this->getHumanTiming($elapsed),
            'elapsed' => $elapsed,
            'break' => $break ? 'Timeout raggiunto. Rilanciare il cron.' : 'Operazione eseguita.',
            'urls_tyre' => $arrayUrlsTyre,
            'urls_nng' => $arrayUrlsNNG,
            'images_tyre' => $imagesTyre,
            'images_nng' => $imagesNng,
        ];

    }

    public function run_old()
    {
        $start = microtime(true);
        $elapsed = 0;
        $break = false;

        //Controllo se c'è stato un aggiornamento recente
        if ($this->updated_date && $this->time_between_updates && $this->status == "DONE") {
            $updated_date = strtotime($this->updated_date);
            $now_time = strtotime('now'); //Trasformo la data in secondi UNIX
            $time_between_updates = (int) $this->time_between_updates;
            $elapsed_time = ($now_time - $updated_date) / 60;
            $human_timing = $this->getHumanTiming($elapsed_time * 60);
            if ($elapsed_time < $time_between_updates) {
                return [
                    'metodo' => 'ReloadImages',
                    'status' => 'DONE',
                    'offset' => 0,
                    'updated' => 0,
                    'httpCode' => 200,
                    'contentType' => 'text/plain',
                    'error' => 0,
                    'error_message' => '',
                    'last_updated_date' => $this->updated_date,
                    'message' => "Ultimo aggiornamento ancora valido: sono passati {$human_timing}",
                    'elapsed' => $elapsed_time
                ];
            }
        }

        if ($this->status == "RESET") {
            $this->offset = 0;
            $this->updated = 0;
            $this->status = "FETCHING";
            $this->deletedImages = "RESET";
            $this->writeStatus();
        }

        $pfx = _DB_PREFIX_;
        $id_category = self::getIdCategoryByName('MAGAZZINO NORD');
        $query = "SELECT id_product from {$pfx}product WHERE id_category_default = {$id_category} ORDER BY id_product LIMIT {$this->offset}, 9999999";
        $products = $this->db->executeS($query);
        $totalProducts = count($products);

        if ($this->deletedImages == 'RESET' || $this->deletedImages == 'DELETING') {
            //Elimino tutte le immagini dei prodotti che sono nella categoria MAGAZZINO NORD
            //TODO: Inserire variabile nella pagina impostazioni per scegliere a quale categoria associare i prodotti

            \PrestaShopLogger::addLog("ReloadImages:: Elimino le immagini dei prodotti. OFFSET: {$this->offset}", 1);

            foreach ($products as $row) {
                $product = new \Product($row['id_product']);
                $deleted = $product->deleteImages();
                if (!$deleted) {
                    \PrestaShopLogger::addLog("Immagini non eliminate per il prodotto " . $row['id_product']);
                }
                $this->offset++;
                $this->deletedImages = 'DELETING';
                \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_OFFSET, $this->offset);
                \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_DELETED_IMAGES, $this->deletedImages);

                $elapsed = microtime(true) - $start;

                if ($elapsed > $this->time_limit) {
                    //Scrivo nei registri lo stato dell'operazione
                    $this->writeStatus();
                    $break = true;
                    break;
                }
            }


            if ($break) {
                $this->deletedImages = "DELETING";
                $this->status = "START";
                $this->writeStatus();

                return [
                    'metodo' => 'ReloadImages: deleteImages',
                    'status' => $this->status,
                    'offset' => $this->offset,
                    'updated' => $this->updated,
                    'httpCode' => 408,
                    'contentType' => 'text/plain',
                    'error' => 408,
                    'error_message' => 'Timeout raggiunto',
                    'message' => 'Operazione in corso',
                    'time' => $this->getHumanTiming($elapsed),
                    'elapsed' => $elapsed,
                    'break' => 'Timeout raggiunto. Rilanciare il cron.',
                ];
            }

            $this->deletedImages = "DONE";
            $this->status = "START";
            $this->offset = 0;
            $this->updated = 0;

            $this->writeStatus();
        }

        foreach ($products as $row) {
            //Leggo il codice del prodotto
            $id_product = (int) $row['id_product'];
            //Compongo il link da scaricare
            //Il logo avrà questa struttura:
            // - https://media3.tyre-shopping.com/images_ts/tyre/<id_product>-w800-h800-br1-24000320301.jpg
            // Immagine originale con logo TYRE
            // - https://media1.tyre-shopping.com/images/tyre/<id_product>.jpg
            $url = self::createTyreUrl($id_product);
            $response = $this->downloadImage($id_product, $url);

            $this->offset++;
            \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_OFFSET, $this->offset);

            if ($response['status'] == 'OK') {
                $this->updated++;
                \Configuration::updateValue(Constants::MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED, $this->updated);
            } else {
                $error = "";
                foreach ($response as $key => $value) {
                    $error .= $key . ": " . $value . "\n";
                }

                \PrestaShopLogger::addLog("MpApiTyres: Loop Download immagine {$id_product}: {$error}", 3);
            }

            $elapsed = microtime(true) - $start;

            if ($elapsed > $this->time_limit) {
                //Scrivo nei registri lo stato dell'operazione
                $this->writeStatus();
                $break = true;
                break;
            }
        }

        if (!$break) {
            $elapsed = microtime(true) - $start;
            $this->deletedImages = "DONE";
            $this->status = "DONE";
            $this->offset = 0;
            $this->writeStatus();
        }

        return [
            'metodo' => 'ReloadImages: downloadImages',
            'status' => $this->status,
            'offset' => $this->offset,
            'updated' => $this->updated,
            'httpCode' => 200,
            'contentType' => 'text/plain',
            'error' => 0,
            'error_message' => '',
            'message' => $this->status == "DONE" ? 'Operazione completata' : 'Operazione in corso',
            'time' => $this->getHumanTiming($elapsed),
            'elapsed' => $elapsed,
            'break' => $break ? 'Timeout raggiunto. Rilanciare il cron.' : 'Operazione eseguita.',
        ];
    }

    public static function getIdCategoryByName($name)
    {
        $category_default = pSQL(\Configuration::get('MPAPITYRES_CATEGORY_NAME'));

        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query->select('id_category')
            ->from('category_lang')
            ->where("name = '{$category_default}'")
            ->where('id_lang = ' . $id_lang);

        return (int) $db->getValue($query) ?: (int) \Configuration::get('PS_DEFAULT_CATEGORY');
    }

    public function downloadImage($id_product, $url)
    {
        /*
        try {
            $image = $this->isValidImageUrl($url);
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog("MpApiTyres: ERRORE Download immagine {$id_product}: {$th->getMessage()}", 3);

            return [
                'status' => 'ERROR',
                'message' => "ERRORE controllo immagine prodotto {$id_product}. URL: {$url}",
                'httpCode' => $th->getCode(),
                'contentType' => $th->getMessage(),
                'url' => $url,
                'id_product' => $id_product,
            ];
        }


        if (!$image['isValid']) {
            $error = "";
            foreach ($image as $key => $value) {
                $error .= $key . ": " . $value . "\n";
            }

            \PrestaShopLogger::addLog("MpApiTyres: ERRORE controllo immagine prodotto {$id_product}. URL: {$url}.\n{$error}", 3);

            return [
                'status' => 'ERROR',
                'message' => "Immagine non valida per il prodotto {$id_product}. URL: {$url}",
                'httpCode' => $image['httpCode'],
                'contentType' => $image['contentType'],
                'addImage' => "--",
                'url' => $url,
                'id_product' => $id_product,
            ];
        }
        */

        $addImageResult = self::addProductImageStatic($id_product, $url);

        if ($addImageResult['status'] == 'ERROR') {
            $error = "";

            foreach ($addImageResult as $key => $value) {
                $error .= $key . ": " . $value . "\n";
            }

            \PrestaShopLogger::addLog("MpApiTyres: Download immagine {$id_product}: {$error}", 3);
            return [
                'status' => 'ERROR',
                'message' => "Immagine non scaricata per il prodotto {$id_product}. URL: {$url}",
                'httpCode' => $addImageResult['httpCode'],
                'error' => $addImageResult['error'],
                'error_message' => $addImageResult['error_message'],
                'contentType' => $addImageResult['contentType'],
                'url' => $url,
                'id_product' => $id_product,
            ];
        }

        return [
            'status' => 'OK',
            'message' => "Immagine scaricata per il prodotto {$id_product}. URL: {$url}",
            'httpCode' => $addImageResult['httpCode'],
            'error' => $addImageResult['error'],
            'error_message' => $addImageResult['error_message'],
            'contentType' => $addImageResult['contentType'],
            'url' => $url,
            'id_product' => $id_product,
        ];
    }
}