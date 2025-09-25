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

namespace MpSoft\MpApiTyres\Controllers;

use MpSoft\MpApiTyres\Helpers\CreateInsertQueryFromJson;
use MpSoft\MpApiTyres\Helpers\ParseTyreCsvHelper;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class ImportCsvController extends FrameworkBundleAdminController
{
    public const CSV_PATH = _PS_UPLOAD_DIR_ . 'CSV/';
    public const CHUNK_PATH = _PS_UPLOAD_DIR_ . 'CSV/chunks';
    public const CSV_FILE = _PS_UPLOAD_DIR_ . 'CSV/tyres_mega.csv';
    public const CSV_ZIP = _PS_UPLOAD_DIR_ . 'CSV/tyres_mega.zip';
    public const CHUNK_SIZE = 10000;
    public function index()
    {
        $chunkFiles = $this->getChunkFiles();
        $params = [
            'chunkFiles' => $chunkFiles
        ];
        $html = $this->renderView(
            '@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/importCSV.html.twig',
            $params
        );

        return $this->json($html);
    }

    protected function getChunkFiles()
    {
        return glob(self::CHUNK_PATH . '/*.json');
    }

    public function readCsvAction()
    {
        $start = microtime(true);
        $filename = self::CSV_FILE;
        $parseTyreCsvHelper = new ParseTyreCsvHelper();
        $parsedCsv = $parseTyreCsvHelper->parse($filename);
        $list = [];
        foreach ($parsedCsv as $row) {
            $list[] = $row;
        }
        $elapsed = microtime(true) - $start;
        return $this->json([
            'list' => $list,
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'size' => $parseTyreCsvHelper->getFileSizeInBytes(),
            'sizeHumanReading' => $parseTyreCsvHelper->getHumanReadingSize(),
        ]);
    }

    public function chunkCsvToJsonAction()
    {
        $csvPath = self::CSV_FILE;
        $outputDir = self::CHUNK_PATH;
        $chunkSize = self::CHUNK_SIZE;

        $helper = new ParseTyreCsvHelper();
        $helper->deleteChunks();
        $helper->resetTotalRows();
        $start = microtime(true);
        $numParts = $helper->csvToJsonChunks($csvPath, $outputDir, $chunkSize);
        $elapsed = microtime(true) - $start;

        return $this->json([
            'chunks_created' => $numParts,
            'output_dir' => $outputDir,
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'total_rows' => ParseTyreCsvHelper::getTotalRows(),
        ]);
    }
    public function downloadJsonAction(Request $request)
    {
        $filename = $request->query->get('file');
        $filePath = self::CHUNK_PATH . '/' . $filename;
        $json = file_get_contents($filePath);
        return $this->json(['json' => $json]);
    }

    public function readJsonAction(Request $request)
    {
        $filename = $request->query->get('file');
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        $data_slice = array_slice($data, 0, 10);

        $query = CreateInsertQueryFromJson::buildBulkInsertQuery($data_slice);
        return $this->json(['json' => $data_slice, 'query' => $query]);
    }

    public function importJsonAction(Request $request)
    {
        $filename = $request->query->get('file');
        $filePath = self::CHUNK_PATH . '/' . $filename;
        $json = file_get_contents($filePath);
        return $this->json(['json' => $json]);
    }

    /**
     * Legge i file presenti nella cartella CSV e restituisce un array con i percorsi dei file
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importChunksAction(Request $request)
    {
        $connection = $this->get('doctrine.dbal.default_connection');
        $prefix = $connection->getDatabasePlatform()->getName() === 'mysql' ? _DB_PREFIX_ : '';
        $tableName = $prefix . 'product_tyre';

        $connection->executeStatement("TRUNCATE TABLE {$tableName}");

        $filePaths = glob(self::CHUNK_PATH . '/*.json');
        return $this->json(['filePaths' => $filePaths]);
    }

    /**
     * Legge un file JSON e restituisce un array di Query INSERT
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importFromChunkAction(Request $request)
    {
        $filePath = $request->request->get('file');
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        $queries = CreateInsertQueryFromJson::buildBulkInsertQuery($data, 2000);

        return $this->json(['success' => true, 'queries' => $queries]);
    }

    /**
     * Legge una query INSERT e la esegue
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importChunkPartAction(Request $request)
    {
        $query = $request->request->get('query');

        $connection = $this->get('doctrine.dbal.default_connection');
        $connection->beginTransaction();
        $result = $connection->executeStatement($query);
        $connection->commit();

        return $this->json(['rows' => $result]);
    }

    public function downloadCatalogAction()
    {
        $start = microtime(true);
        $curlCatalogDownload = new CurlCatalogDownload();
        $curlCatalogDownload->downloadCatalog();
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
        ]);
    }
}
