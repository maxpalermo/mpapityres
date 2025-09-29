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
use MpSoft\MpApiTyres\Catalog\ImportCatalog;
use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Const\Constants;
use MpSoft\MpApiTyres\Curl\GetCatalogApi;
use MpSoft\MpApiTyres\Import\CreatePfu;
use MpSoft\MpApiTyres\Import\ReloadImages;
use MpSoft\MpApiTyres\Traits\GetCategoryIdFromNameTrait;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;
use MpSoft\MpApiTyres\Zip\DownloadZip;
use MpSoft\MpApiTyres\Zip\ParseCsv;

class MpApiTyresCronModuleFrontController extends ModuleFrontController
{
    use HumanTimingTrait;
    use GetCategoryIdFromNameTrait;

    protected $id_lang;
    private $configValues;

    public function __construct()
    {
        parent::__construct();
        $this->configValues = ConfigValues::getInstance();
    }

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

        exit(json_encode([
            'success' => false,
            'error' => "metodo {$action} non esistente",
        ]));
    }

    public function getCatalogAction()
    {
        $reset = (int) Tools::getValue('reset');
        $configValues = ConfigValues::getInstance();

        if ($reset) {
            $configValues->MPAPITYRES_CRON_DOWNLOAD_OFFSET = 0;
            $configValues->setValue('MPAPITYRES_CRON_DOWNLOAD_OFFSET', 0);

            $configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED = 0;
            $configValues->setValue('MPAPITYRES_CRON_DOWNLOAD_UPDATED', 0);

            $configValues->MPAPITYRES_CRON_DOWNLOAD_STATUS = 'DONE';
            $configValues->setValue('MPAPITYRES_CRON_DOWNLOAD_STATUS', 'DONE');

            $configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE = false;
            $configValues->setValue('MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE', false);
        }

        $downloadCatalog = new DownloadCatalog();
        $response = $downloadCatalog->run();

        return $response;
    }

    public function importCatalogAction()
    {
        $reset = (int) Tools::getValue('reset');

        if ($reset) {
            $this->configValues->MPAPITYRES_CRON_IMPORT_STATUS = "DONE";
            $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_STATUS', "DONE");

            $this->configValues->MPAPITYRES_CRON_IMPORT_UPDATED_DATE = false;
            $this->configValues->setValue('MPAPITYRES_CRON_IMPORT_UPDATED_DATE', false);
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

    public function deleteProductsAction()
    {
        $MAX_TIMEOUT = 60; //secondi
        $id_lang = (int) Configuration::get("PS_LANG_DEFAULT");
        $pfx = _DB_PREFIX_;
        $start = microtime(true);
        $category_default = pSQL(Configuration::get('MPAPITYRES_DEFAULT_CATEGORY'));

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

    private function createPfuAction()
    {
        $idStart = Tools::getValue('idStart');
        $idTaxRulesGroup = Tools::getValue('taxRuleGroup');
        $priceList = Tools::getValue('priceList');
        $list = explode("\n", $priceList);
        foreach ($list as $key => $item) {
            $value = trim($item);
            if (!$value) {
                unset($list[$key]);
            }
            if (!is_numeric($value)) {
                unset($list[$key]);
            }
        }
        $totalProducts = count($list);

        if (!$totalProducts) {
            return [
                'success' => false,
                'error' => 'Listino prezzi non valido',
            ];
        }

        $createPFU = new CreatePfu($totalProducts, $idStart, $idTaxRulesGroup, $priceList);
        $createPFU->run();

        return [
            'success' => true,
        ];
    }

    /**
     * Avvia il download del catalogo ZIP da un endpoint specificato.
     * action=startCatalogDownloadAction
     */
    public function startCatalogDownloadAction()
    {
        $downloadZip = new DownloadZip();

        header('Content-Type: application/json');
        $endpoint = Tools::getValue('endpoint');
        if (!$endpoint) {
            return [
                'status' => 'error',
                'message' => 'Endpoint mancante'
            ];
        }
        try {
            $file = $downloadZip->downloadZip($endpoint);
            return [
                'status' => 'started',
                'file' => $file
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Restituisce lo stato di progresso del download per un dato endpoint.
     * action=getCatalogDownloadProgressAction
     */
    public function getCatalogDownloadProgressAction()
    {
        $downloadZip = new DownloadZip();

        header('Content-Type: application/json');
        $endpoint = Tools::getValue('endpoint');
        if (!$endpoint) {
            return [
                'status' => 'error',
                'message' => 'Endpoint mancante'
            ];
        }

        $progress = $downloadZip->getCsvDownloadProgress($endpoint);
        if ($progress === null) {
            return [
                'status' => 'not_found'
            ];
        }
        return [
            'status' => 'ok',
            'progress' => $progress
        ];
    }

    /**
     * Estrae il file ZIP già scaricato.
     * action=extractCatalogZipAction
     */
    public function extractCatalogZipAction()
    {
        $downloadZip = new DownloadZip();

        header('Content-Type: application/json');
        $file = Tools::getValue('file');
        if (!$file) {
            return [
                'status' => 'error',
                'message' => 'File mancante'
            ];
        }

        try {
            $files = $downloadZip->extractZip($file);
            return [
                'status' => 'ok',
                'files' => $files
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Ritorna lo stato di progresso del parsing CSV.
     * action=getCsvParseProgressAction
     * Parametri: csvPath (relativo o assoluto), progressId (opzionale)
     */
    public function getCsvParseProgressAction()
    {
        header('Content-Type: application/json');
        $parseCsv = new ParseCsv();
        $csvPathParam = Tools::getValue('csvPath');
        $progressId = Tools::getValue('progressId');

        if (!$csvPathParam) {
            return [
                'status' => 'error',
                'message' => 'csvPath mancante'
            ];
        }

        $csvPath = $csvPathParam;
        if (strpos($csvPath, _PS_MODULE_DIR_) !== 0) {
            $csvPath = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/') . '/' . ltrim($csvPathParam, '/');
        }

        $progress = $parseCsv->getCsvParseProgress($csvPath, $progressId);
        if ($progress === null) {
            return [
                'status' => 'not_found'
            ];
        }
        return [
            'status' => 'ok',
            'progress' => $progress
        ];
    }

    /**
     * Resetta stato e progresso del parsing CSV, opzionalmente cancella righe type='CSV'.
     * action=resetCsvParseAction
     * Parametri: csvPath, progressId (opz), clearRows (default 0)
     */
    public function resetCsvParseAction()
    {
        header('Content-Type: application/json');
        $parseCsv = new ParseCsv();
        $csvPathParam = Tools::getValue('csvPath');
        $progressId = Tools::getValue('progressId');
        $clearRows = (int) Tools::getValue('clearRows', 0) ? true : false;

        try {
            if (!$csvPathParam) {
                return [
                    'status' => 'error',
                    'message' => 'csvPath mancante'
                ];
            }

            $csvPath = $csvPathParam;

            if (strpos($csvPath, _PS_MODULE_DIR_) !== 0) {
                $csvPath = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/') . '/' . ltrim($csvPathParam, '/');
            }

            if (!is_file($csvPath)) {
                return [
                    'status' => 'error',
                    'message' => 'File CSV non trovato: ' . $csvPath
                ];
            }

            $progressKey = $progressId ? ('parse_' . $progressId) : $parseCsv->getParseProgressKeyFromFile($csvPath);
            $parseCsv->clearParseState($progressKey);
            $parseCsv->clearParseProgress($progressKey);

            if ($clearRows) {
                $db = \Db::getInstance();
                try {
                    $db->execute("COMMIT");
                } catch (\Throwable $th) {
                    // nothing
                }
                $db->delete(
                    'product_tyre',
                    "type = 'CSV'"
                );
            }

            return [
                'status' => 'ok'
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Esegue il parsing a step del CSV con stato persistente.
     * action=stepCsvParseAction
     * Parametri: csvPath, delimiter (default '|'), progressId (opz), timeBudgetMs (default 1500), batchSize (default 500), clearFirst (default 1)
     */
    public function stepCsvParseAction()
    {
        header('Content-Type: application/json');
        $parseCsv = new ParseCsv();
        $csvPathParam = Tools::getValue('csvPath');
        $delimiter = Tools::getValue('delimiter', '|');
        $progressId = Tools::getValue('progressId');
        $timeBudgetMs = (int) Tools::getValue('timeBudgetMs', 1500);
        $batchSize = (int) Tools::getValue('batchSize', 500);
        $clearFirst = (int) Tools::getValue('clearFirst', 1) ? true : false;

        try {
            if (!$csvPathParam) {
                echo json_encode(['status' => 'error', 'message' => 'csvPath mancante']);
                exit;
            }
            $csvPath = $csvPathParam;
            if (strpos($csvPath, _PS_MODULE_DIR_) !== 0) {
                $csvPath = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/') . '/' . ltrim($csvPathParam, '/');
            }
            if (!is_file($csvPath)) {
                echo json_encode(['status' => 'error', 'message' => 'File CSV non trovato: ' . $csvPath]);
                exit;
            }

            $progressKey = $progressId ? ('parse_' . $progressId) : $parseCsv->getParseProgressKeyFromFile($csvPath);
            $state = $parseCsv->readParseState($progressKey) ?: [];
            $db = \Db::getInstance();
            $pfx = _DB_PREFIX_;

            // Prima volta: pulizia righe CSV e init header
            if (empty($state)) {
                if ($clearFirst) {
                    $db->execute("DELETE FROM {$pfx}product_tyre WHERE type='CSV'");
                }
                $parseCsv->writeParseProgressPayload($progressKey, [
                    'status' => 'starting',
                    'file' => basename($csvPath),
                    'total_bytes' => (@filesize($csvPath) ?: 0),
                    'read_bytes' => 0,
                    'percent' => 0,
                    'rows' => 0,
                    'updated_at' => date('c'),
                ]);
            }

            $fp = fopen($csvPath, 'r');
            if ($fp === false) {
                echo json_encode(['status' => 'error', 'message' => 'Impossibile aprire il CSV']);
                exit;
            }

            $totalSize = @filesize($csvPath) ?: 0;
            $rowsTotal = isset($state['rows_total']) ? (int) $state['rows_total'] : 0;
            $offset = isset($state['offset']) ? (int) $state['offset'] : 0;
            $header = isset($state['header']) && is_array($state['header']) ? $state['header'] : null;
            $start = microtime(true);
            $batch = [];
            $nowTs = date('Y-m-d H:i:s');

            // Header
            if ($header === null) {
                $header = fgetcsv($fp, 0, $delimiter);
                if ($header === false || count($header) === 0) {
                    fclose($fp);
                    echo json_encode(['status' => 'error', 'message' => 'Header CSV non valido']);
                    exit;
                }
                $offset = ftell($fp);
            } else {
                fseek($fp, $offset);
            }

            // Loop fino a time budget
            while (true) {
                $row = fgetcsv($fp, 0, $delimiter);
                if ($row === false) {
                    // Fine file
                    break;
                }
                if (count($row) === 1 && trim($row[0]) === '') {
                    $offset = ftell($fp);
                    if ((microtime(true) - $start) * 1000 >= $timeBudgetMs)
                        break;
                    continue;
                }
                // Associa colonna
                $assoc = array_combine($header, $row);
                if ($assoc === false) {
                    $assoc = [];
                    foreach ($header as $i => $col) {
                        $assoc[$col] = $row[$i] ?? null;
                    }
                }

                $id_t24 = isset($assoc['id']) ? (string) $assoc['id'] : '';
                $matchcode = isset($assoc['matchcode']) ? (string) $assoc['matchcode'] : '';


                $content = json_encode($assoc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $values = "('" . pSQL($id_t24) . "','CSV','" . pSQL($matchcode) . "','" . pSQL($content) . "',1,'{$nowTs}',NULL)";
                $batch[] = $values;
                $rowsTotal++;


                if (count($batch) >= $batchSize) {
                    $sql = "INSERT INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, date_add, date_upd) VALUES " . implode(',', $batch);
                    $db->execute($sql);
                    $batch = [];
                }

                $offset = ftell($fp);

                if ((microtime(true) - $start) * 1000 >= $timeBudgetMs) {
                    break;
                }
            }

            if (!empty($batch)) {
                $sql = "INSERT INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, date_add, date_upd) VALUES " . implode(',', $batch);
                $db->execute($sql);
            }

            $percent = ($totalSize > 0 && $offset > 0) ? (int) floor(($offset / $totalSize) * 100) : 0;
            $status = ($offset >= $totalSize) ? 'done' : 'parsing';
            $parseCsv->writeParseProgressPayload($progressKey, [
                'status' => $status,
                'file' => basename($csvPath),
                'total_bytes' => $totalSize,
                'read_bytes' => $offset,
                'percent' => $status === 'done' ? 100 : $percent,
                'rows' => $rowsTotal,
                'updated_at' => date('c'),
            ]);

            // Aggiorna stato
            if ($status === 'done') {
                fclose($fp);
                $parseCsv->clearParseState($progressKey);
                $duplicates = $this->deleteMatchcodeDuplicates('product_tyre');

                return [
                    'status' => 'done',
                    'rows' => $rowsTotal,
                    'duplicates' => $duplicates,
                ];
            } else {
                $parseCsv->writeParseState($progressKey, [
                    'header' => $header,
                    'offset' => $offset,
                    'rows_total' => $rowsTotal,
                ]);
                fclose($fp);
                return [
                    'status' => 'ok',
                    'rows' => $rowsTotal,
                    'percent' => $percent
                ];
            }
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteMatchcodeDuplicates($tablename, $matchcode_field = 'matchcode')
    {
        return 0;
    }

    /**
     * Restituisce il CSV più recente presente in modules/mpapityres/downloads/csv/ (ricorsivo)
     * action=getLatestExtractedCsvAction
     */
    public function getLatestExtractedCsvAction()
    {
        header('Content-Type: application/json');
        $base = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/');
        $root = $base . '/' . 'downloads/csv/';
        if (!is_dir($root)) {
            echo json_encode(['status' => 'not_found', 'message' => 'Cartella CSV non trovata']);
            exit;
        }

        $latestFile = null;
        $latestMtime = 0;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $file) {
            if ($file->isFile() && preg_match('/\.csv$/i', $file->getFilename())) {
                $mtime = $file->getMTime();
                if ($mtime > $latestMtime) {
                    $latestMtime = $mtime;
                    $latestFile = $file->getPathname();
                }
            }
        }

        if (!$latestFile) {
            echo json_encode(['status' => 'not_found']);
            exit;
        }

        $relative = ltrim(str_replace($base . '/', '', $latestFile), '/');
        $size = @filesize($latestFile) ?: 0;
        echo json_encode([
            'status' => 'ok',
            'file' => $relative,
            'size' => $size,
            'mtime' => $latestMtime,
        ]);
        exit;
    }

    /**
     * Avvia (esegue) il parsing del CSV e aggiorna il file di progresso.
     * action=startCsvParseAction
     * Parametri: csvPath (relativo al modulo o assoluto), delimiter (opzionale), progressId (opzionale)
     */
    public function startCsvParseAction()
    {
        header('Content-Type: application/json');
        $parseCsv = new ParseCsv();
        $csvPathParam = Tools::getValue('csvPath');
        $delimiter = Tools::getValue('delimiter', '|');
        $progressId = Tools::getValue('progressId');

        try {
            // Normalizza percorso: consenti assoluto oppure relativo al modulo
            if (!$csvPathParam) {
                echo json_encode(['status' => 'error', 'message' => 'csvPath mancante']);
                exit;
            }
            $csvPath = $csvPathParam;
            if (strpos($csvPath, _PS_MODULE_DIR_) !== 0) {
                $csvPath = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/') . '/' . ltrim($csvPathParam, '/');
            }
            if (!is_file($csvPath)) {
                echo json_encode(['status' => 'error', 'message' => 'File CSV non trovato: ' . $csvPath]);
                exit;
            }

            // Cancella le righe pregresse importate da CSV
            $db = \Db::getInstance();
            $pfx = _DB_PREFIX_;
            $db->execute("DELETE FROM {$pfx}product_tyre WHERE type='CSV'");

            $count = 0;
            $batch = [];
            $batchSize = 500; // dimensione batch
            $now = date('Y-m-d H:i:s');

            foreach ($parseCsv->parseCSV($csvPath, $delimiter, $progressId) as $row) {
                $id_t24 = isset($row['idT24']) ? (string) $row['idT24'] : '';
                $matchcode = isset($row['matchCode']) ? (string) $row['matchCode'] : '';
                $content = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $values = "('" . pSQL($id_t24) . "','CSV','" . pSQL($matchcode) . "','" . pSQL($content) . "',1,'{$now}',NULL)";
                $batch[] = $values;
                $count++;

                if (count($batch) >= $batchSize) {
                    $sql = "INSERT INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, date_add, date_upd) VALUES " . implode(',', $batch);
                    $db->execute($sql);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $sql = "INSERT INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, date_add, date_upd) VALUES " . implode(',', $batch);
                $db->execute($sql);
            }

            echo json_encode(['status' => 'ok', 'rows' => $count]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function disableProductsAction()
    {
        $category_name = Configuration::get("MPAPITYRES_CATEGORY_DEFAULT");
        $id_category_default = $this->getIdCategoryFromName($category_name);
        $db = Db::getInstance();
        $db->update(
            'product',
            [
                'active' => 0,
                'id_category_default' => $id_category_default,
            ],
        );
        $disabled_products = $db->Affected_Rows();

        $db->update(
            'product_shop',
            [
                'active' => 0,
                'id_category_default' => $id_category_default,
            ],
        );
        $disabled_products_shop = $db->Affected_Rows();

        return [
            'status' => 'ok',
            'disabled_products' => $disabled_products,
            'disabled_products_shop' => $disabled_products_shop,
        ];
    }

    public function resetPrestashopCatalogAction()
    {
        $db = Db::getInstance();
        $pfx = _DB_PREFIX_;

        $db->execute("TRUNCATE TABLE {$pfx}product");
        $db->execute("TRUNCATE TABLE {$pfx}product_lang");
        $db->execute("TRUNCATE TABLE {$pfx}product_shop");
        $db->execute("TRUNCATE TABLE {$pfx}image");
        $db->execute("TRUNCATE TABLE {$pfx}image_lang");
        $db->execute("TRUNCATE TABLE {$pfx}image_shop");
        $db->execute("TRUNCATE TABLE {$pfx}feature_product");
        $db->execute("TRUNCATE TABLE {$pfx}stock_available");
        $db->execute("TRUNCATE TABLE {$pfx}stock_mvt");

        $img_folder = _PS_PROD_IMG_DIR_;
        //Elimino tutte le sottocartelle da 1 a 9, devono essere eliminate anche se non sono vuote

        $this->deleteAllSubfolders($img_folder);

        return [
            'success' => 1
        ];

    }

    /**
     * Rimuove ricorsivamente una directory (anche se non vuota).
     */
    function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ri as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @chmod($path, 0777);
                @rmdir($path);
            } else {
                @chmod($path, 0666);
                @unlink($path);
            }
        }

        @chmod($dir, 0777);
        @rmdir($dir);
    }

    /**
     * Elimina tutte le sottocartelle della cartella $root, lasciando intatti eventuali file in $root.
     */
    function deleteAllSubfolders(string $root): void
    {
        if (!is_dir($root)) {
            throw new InvalidArgumentException("Percorso non valido: {$root}");
        }

        // Piccola salvaguardia: evita path pericolosi
        $real = rtrim(realpath($root), DIRECTORY_SEPARATOR);
        if ($real === '' || $real === DIRECTORY_SEPARATOR) {
            throw new RuntimeException("Percorso troppo rischioso: {$root}");
        }

        $it = new FilesystemIterator($real, FilesystemIterator::SKIP_DOTS);
        foreach ($it as $entry) {
            if ($entry->isDir()) {
                $this->rrmdir($entry->getPathname());
            }
        }
    }
}