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

use MpSoft\MpApiTyres\Catalog\GetCatalogApi;
use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;

class DownloadCatalog
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
    private $min_stock;
    private $configValues;

    public function __construct()
    {
        $this->configValues = ConfigValues::getInstance();
        $this->db = \Db::getInstance();
        $this->limit = 3000;
        $this->time_limit = $this->configValues->MPAPITYRES_CURL_TIMEOUT;
        $this->time_between_updates = $this->configValues->MPAPITYRES_CRON_TIME_BETWEEN_UPDATES;
        $this->readStatus();
    }

    protected function writeStatus()
    {
        $this->configValues->MPAPITYRES_CRON_DOWNLOAD_OFFSET = $this->offset;
        $this->configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED = $this->updated;
        $this->configValues->MPAPITYRES_CRON_DOWNLOAD_STATUS = $this->status;
        $this->configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE = $this->updated_date;
        $this->configValues->writeConfigValues();
    }

    protected function readStatus()
    {
        $this->offset = (int) $this->configValues->MPAPITYRES_CRON_DOWNLOAD_OFFSET ?: 0;
        $this->updated = (int) $this->configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED ?: 0;
        $this->status = $this->configValues->MPAPITYRES_CRON_DOWNLOAD_STATUS ?: "FETCHING";
        $this->updated_date = $this->configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE ?: false;
        $this->min_stock = $this->configValues->MPAPITYRES_API_MIN_STOCK ?: 4;
    }

    public function run()
    {
        $start = microtime(true);
        $elapsed = 0;

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
            $this->updated_date = null;
            $this->writeStatus();
        }

        if ($this->offset == 0) {
            //Svuoto la tabella di download prodotti API
            $pfx = _DB_PREFIX_;
            $query = "DELETE FROM {$pfx}product_tyre WHERE type = 'API'";

            try {
                $truncate = $this->db->execute($query);
            } catch (\Throwable $th) {
                return [
                    'metodo' => 'DownloadCatalog',
                    'status' => 'ERROR',
                    'offset' => $this->offset,
                    'updated' => $this->updated,
                    'message' => 'Errore durante lo svuotamento della tabella product_tyre',
                    'time' => $this->getHumanTiming($elapsed),
                    'elapsed' => $elapsed,
                ];
            }
            if (!$truncate) {
                return [
                    'metodo' => 'DownloadCatalog',
                    'status' => 'ERROR',
                    'offset' => $this->offset,
                    'updated' => $this->updated,
                    'message' => "Errore {$this->db->getMsgError()} durante lo svuotamento della tabella product_tyre",
                    'time' => $this->getHumanTiming($elapsed),
                    'elapsed' => $elapsed,
                ];
            }
        }

        //Preparo i dati per il download via API
        $host = $this->configValues->MPAPITYRES_API_ENDPOINT;
        $token = $this->configValues->MPAPITYRES_API_TOKEN;
        $endpoint = "/search";

        //Scarico i dati da Tyre e li inserisco nella tabella product_tyre
        $catalogAPI = new GetCatalogApi($host, $endpoint, $token, $this->time_limit);
        $result = $catalogAPI->run($this->offset, $this->limit, $this->min_stock);

        $this->offset += $result['count'];
        $this->updated += $result['updated'];

        $elapsed = microtime(true) - $start;

        if ($result['count'] == 0) {
            $this->status = "DONE";
            $this->offset = 0;
            $this->updated_date = date('Y-m-d H:i:s');
        } else {
            if ($elapsed > $this->time_limit) {
                $this->status = "FETCHING";
            }
        }

        $this->writeStatus();

        return [
            'metodo' => 'DownloadCatalog',
            'status' => $this->status,
            'offset' => $this->offset,
            'updated' => $this->updated,
            'message' => $this->status == "DONE" ? 'Operazione completata' : 'Operazione in corso',
            'time' => $this->getHumanTiming($elapsed),
            'elapsed' => $elapsed,
        ];
    }
}