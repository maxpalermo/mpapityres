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

namespace MpSoft\MpApiTyres\Catalog;

use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;

class ImportCatalog
{
    use HumanTimingTrait;

    private $db;
    private $offset;
    private $limit;
    private $updated;
    private $status;
    private $time_limit;
    private $updated_date;
    private $time_between_updates;
    private $deactivated;

    private $configValues;

    public function __construct()
    {
        $this->configValues = ConfigValues::getInstance();
        $this->db = \Db::getInstance();
        $this->limit = 999999999;
        $this->time_limit = ((int) $this->configValues->MPAPITYRES_CURL_TIMEOUT) ?: 60; //secondi
        $this->time_between_updates = $this->configValues->MPAPITYRES_CRON_TIME_BETWEEN_UPDATES ?: 120;
        $this->readStatus();
    }

    protected function writeStatus()
    {
        $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_OFFSET', $this->offset);
        $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_UPDATED', $this->updated);
        $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_STATUS', $this->status);
        $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_UPDATED_DATE', $this->updated_date);
    }

    protected function readStatus()
    {
        $this->offset = (int) $this->configValues->MPAPITYRES_CRON_IMPORT_OFFSET ?: 0;
        $this->updated = (int) $this->configValues->MPAPITYRES_CRON_IMPORT_UPDATED ?: 0;
        $this->status = $this->configValues->MPAPITYRES_CRON_IMPORT_STATUS ?: "FETCHING";
        $this->updated_date = $this->configValues->MPAPITYRES_CRON_IMPORT_UPDATED_DATE ?: false;
    }

    protected function checkDownloadStatus()
    {
        $downloadStatus = \Configuration::get('MPAPITYRES_CRON_DOWNLOAD_STATUS');
        if ($downloadStatus != 'DONE') {
            return false;
        }

        return true;
    }

    public function run()
    {
        if (!$this->checkDownloadStatus()) {
            return [
                'metodo' => 'ImportCatalog',
                'status' => 'ERROR',
                'offset' => $this->offset,
                'updated' => $this->updated,
                'message' => 'Operazione non consentita. Scaricamento del Catalogo in corso.',
                'time' => $this->getHumanTiming(0),
                'elapsed' => 0,
            ];
        }

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
                    'metodo' => 'DownloadCatalog',
                    'status' => 'DONE',
                    'offset' => 0,
                    'updated' => 0,
                    'last_updated_date' => $this->updated_date,
                    'message' => "Ultimo aggiornamento ancora valido: sono passati {$human_timing}",
                    'elapsed' => $elapsed_time
                ];
            }
        }

        if ($this->status == "DONE") {
            $this->offset = 0;
            $this->updated = 0;
            $this->status = "FETCHING";
            $this->writeStatus();
        }

        if ($this->offset == 0 && 1 == 0) {
            //Disattivo tutti i prodotti della categoria PNEUMATICI nella tabella product che non siano presenti in tyre
            $pfx = _DB_PREFIX_;
            $id_category = self::getIdCategoryByName('PNEUMATICI');
            $where = "id_product NOT IN (SELECT id_t24 FROM {$pfx}product_tyre) AND id_category_default = {$id_category}";

            $this->db->update(
                'product',
                [
                    'active' => 0
                ],
                $where
            );

            $this->db->update(
                'product_shop',
                [
                    'active' => 0
                ],
                $where
            );

            $this->deactivated = (int) $this->db->Affected_Rows();
        }

        $rows = $this->getTyresRows();
        foreach ($rows as $row) {
            //Decodifico il prodotto dal valore JSON della tabella
            $tyre = json_decode($row['content'], true);
            //Chiamo la funzione che importa il prodotto nel catalogo Prestashop
            $updateCatalogProduct = new UpdateCatalog();
            $response = $updateCatalogProduct->updateProduct($tyre);

            if ($response) {
                $this->offset++;
                $this->updated++;

                $this->configValues->MPAPITYRES_CRON_IMPORT_OFFSET = $this->offset;
                $this->configValues->MPAPITYRES_CRON_IMPORT_UPDATED = $this->updated;

                $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_OFFSET', $this->offset);
                $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_UPDATED', $this->updated);
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
            $this->status = "DONE";
            $this->offset = 0;
            $this->writeStatus();
        }

        return [
            'metodo' => 'ImportCatalog',
            'status' => $this->status,
            'offset' => $this->offset,
            'updated' => $this->updated,
            'message' => $this->status == "DONE" ? 'Operazione completata' : 'Operazione in corso',
            'time' => $this->getHumanTiming($elapsed),
            'elapsed' => $elapsed,
            'break' => $break ? 'Timeout raggiunto. Rilanciare il cron.' : 'Operazione eseguita.',
        ];
    }

    public function getTyresRows()
    {
        $query = new \DbQuery();
        $query->select('*')
            ->from('product_tyre')
            ->where('type = \'API\'')
            ->limit(999999999, $this->offset)
            ->orderBy('id_t24 DESC');
        return $this->db->executeS($query);
    }

    public static function getIdCategoryByName($name)
    {
        $name = pSQL($name);
        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query->select('id_category')
            ->from('category_lang')
            ->where('name = "{$name}"')
            ->where('id_lang = ' . $id_lang);

        return (int) $db->getValue($query) ?: (int) \Configuration::get('PS_DEFAULT_CATEGORY');
    }
}