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

use MpSoft\MpApiTyres\Controllers\ImportCsvController;
use Symfony\Component\Dotenv\Dotenv;

class CurlCatalogDownload extends DependencyHelper
{
    public const CATALOG_URL = 'https://tyre24.alzura.com/it/it/export/download-via-token/token/{token}/accountId/{accountId}/t/1/c/35/';

    public function downloadCatalog()
    {
        //Leggi il file .env e prendi le variabili
        $dotenv = new Dotenv();
        $dotenv->load(_PS_MODULE_DIR_ . 'mpapityres/.env');

        $token = $_ENV['CATALOG_DOWNLOAD_TOKEN'];
        $accountId = $_ENV['USER_NAME'];

        $url = str_replace(['{token}', '{accountId}'], [$token, $accountId], self::CATALOG_URL);
        $zipFileName = ImportCsvController::CSV_ZIP;
        if ($this->downloadFile($url, $zipFileName)) {
            $this->unzipFile($zipFileName);
            if (file_exists(ImportCsvController::CSV_FILE)) {
                return true;
            }
        }

        throw new \Exception('File CSV non trovato');
    }

    protected function downloadFile($url, $outputPath)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        return file_put_contents($outputPath, $data);
    }

    protected function unzipFile($zipPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            // Cerca il file .csv dentro la cartella tmp
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];
                if (preg_match('#^tmp/.+\.csv$#i', $filename)) {
                    // Estrai il file in memoria
                    $csvContent = $zip->getFromIndex($i);
                    $dest = ImportCsvController::CSV_FILE;
                    file_put_contents($dest, $csvContent);
                    break;
                }
            }
            $zip->close();
        }
    }
}
