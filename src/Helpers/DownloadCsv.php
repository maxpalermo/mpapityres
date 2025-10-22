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

namespace MpSoft\MpApiTyres\Helpers;

use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Zip\ParseCsv;

class DownloadCsv
{
    public static function getZipAndExtract()
    {
        $configValues = ConfigValues::getInstance();
        $csvEndpointUrl = $configValues->getCsvEndpointUrl();

        // Scarico il file ZIP tramite guzzleHTTP
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $csvEndpointUrl);
        $body = $response->getBody();
        $file = $body->getContents();
        $downloadFolder = _PS_ROOT_DIR_ . '/download/csv/';

        // Salvo il file ZIP in /var/www/html/tyre24/zip
        if (!file_exists($downloadFolder)) {
            mkdir($downloadFolder, 0777, true);
        }

        $zipFileName = date('YmdHis') . '.zip';
        $zipFile = $downloadFolder . $zipFileName;

        file_put_contents($zipFile, $file);

        // Estraggo il file CSV dallo ZIP senza creare cartelle
        $filename = '';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            // Cerca il file CSV all'interno dello ZIP (anche in sottocartelle)
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // Controlla se è un file CSV (non una cartella)
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'csv') {
                    // Estrae solo il contenuto del CSV
                    $csvContent = $zip->getFromIndex($i);
                    // Salva il file CSV direttamente nella cartella di destinazione
                    $csvFile = $downloadFolder . basename($filename);
                    file_put_contents($csvFile, $csvContent);
                    break;
                }
            }
            $zip->close();
        }

        // Cancello il file ZIP
        unlink($zipFile);

        return "File {$filename} estratto con successo";
    }

    public static function parseCsvByStep()
    {
        $parseCsv = new ParseCsv();
        $csvPathParam = \Tools::getValue('csvPath');
        $delimiter = \Tools::getValue('delimiter', '|');
        $progressId = \Tools::getValue('progressId');
        $timeBudgetMs = (int) \Tools::getValue('timeBudgetMs', 1500);
        $batchSize = (int) \Tools::getValue('batchSize', 500);
        $clearFirst = (int) \Tools::getValue('clearFirst', 1) ? true : false;

        try {
            if (!$csvPathParam) {
                return "csvPath {$csvPathParam} mancante";
            }
            $csvPath = $csvPathParam;
            if (strpos($csvPath, _PS_MODULE_DIR_) !== 0) {
                $csvPath = rtrim(_PS_MODULE_DIR_ . 'mpapityres/', '/') . '/' . ltrim($csvPathParam, '/');
            }
            if (!is_file($csvPath)) {
                return "File {$csvPath} non trovato.";
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
                return "Impossibile aprire il CSV {$csvPath}";
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
                    return 'Header CSV non valido';
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
                $duplicates = self::deleteMatchCodeDuplicates('product_tyre');

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

    public static function parseCsvTotal()
    {
        $id_account = \Configuration::get('MPAPITYRES_CSV_ACCOUNT_ID');
        $parseCsv = new ParseCsv();
        $csvPath = _PS_ROOT_DIR_ . '/download/csv/';
        $csvFileName = "{$id_account}_it.csv";
        $delimiter = \Tools::getValue('delimiter', '|');

        try {
            if (!$csvPath) {
                return 'csvPath mancante';
            }

            if (!is_file($csvPath . $csvFileName)) {
                return "File CSV non trovato: {$csvPath}{$csvFileName}";
            }

            $batchSize = 500;

            $fp = fopen($csvPath . $csvFileName, 'r');

            if ($fp === false) {
                return "Impossibile aprire il file CSV: {$csvPath}{$csvFileName}";
            }

            // Leggo la prima riga (Header)
            $headerCsv = fgetcsv($fp, 0, $delimiter);
            if ($headerCsv === false || count($headerCsv) === 0) {
                fclose($fp);
                return 'Header CSV non valido';
            }
            $header = array_map('trim', $headerCsv);

            // Disattivo tutte le righe
            $db = \Db::getInstance();
            $pfx = _DB_PREFIX_;
            $db->execute("UPDATE {$pfx}product_tyre SET active = 0 WHERE type='CSV'");

            // Inizio la lettura del file a blocchi
            $rowsTotal = 0;
            $batchRows = [];
            $loadAmount = (float) \Configuration::get('MPAPITYRES_RICARICO_PREZZO');
            $loadPerc = (float) \Configuration::get('MPAPITYRES_RICARICO_DEFAULT');
            while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                $assoc = array_combine($header, $row);
                if ($assoc !== false) {
                    $assoc['loadAmount'] = $loadAmount;
                    $assoc['loadPerc'] = $loadPerc;
                    $batchRows[] = $assoc;
                }

                $rowsTotal++;

                // Processa il batch ogni $batchSize righe
                if ($rowsTotal % $batchSize === 0) {
                    $insert = self::insertRows($batchRows);
                    $batchRows = [];
                }
            }

            fclose($fp);

            return "Lette {$rowsTotal} righe.";
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function deleteMatchCodeDuplicates($tablename, $matchcode_field = 'matchcode')
    {
        return 0;
    }

    public static function addPriceLoad($price, $loadAmount, $loadPerc)
    {
        $price += $loadAmount;
        return $price + ($price * $loadPerc / 100);
    }

    public static function insertRows($rows)
    {
        $pfx = _DB_PREFIX_;
        $db = \Db::getInstance();
        $batch = [];
        $nowTs = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $id_t24 = isset($row['id']) ? (string) $row['id'] : '';
            $matchcode = isset($row['matchcode']) ? (string) $row['matchcode'] : '';

            $id_t24 = (int) $id_t24;
            $matchcode = pSQL((string) $matchcode);
            $content = pSQL(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $loadAmount = (float) $row['loadAmount'];
            $loadPerc = (float) $row['loadPerc'];
            $price_1 = (float) $row['price_1'];
            $price_4 = (float) $row['price_4'];
            $loaded_price_1 = self::addPriceLoad($price_1, $loadAmount, $loadPerc);
            $loaded_price_4 = self::addPriceLoad($price_4, $loadAmount, $loadPerc);
            $values = "({$id_t24}, 'CSV', '{$matchcode}', '{$content}', 1, {$price_1}, {$price_4}, {$loadAmount}, {$loadPerc}, {$loaded_price_1}, {$loaded_price_4}, '{$nowTs}', '{$nowTs}')";
            $batch[] = $values;
        }
        try {
            $sql = "INSERT INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, price_unit, price_set, load_amount, load_perc, price_unit_loaded, price_set_loaded, date_add, date_upd) VALUES " . implode(',', $batch);
            $result = $db->execute($sql);
        } catch (\Throwable $th) {
            $sql = "REPLACE INTO {$pfx}product_tyre (id_t24, type, matchcode, content, active, price_unit, price_set, load_amount, load_perc, price_unit_loaded, price_set_loaded, date_add, date_upd) VALUES " . implode(',', $batch);
            $result = $db->execute($sql);
        }

        return $result;
    }
}
