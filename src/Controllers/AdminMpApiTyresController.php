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

use MpSoft\MpApiTyres\Catalog\ProductTyreApi;
use MpSoft\MpApiTyres\Catalog\UpdateCatalog;
use MpSoft\MpApiTyres\Handlers\FetchDistributorListHandler;
use MpSoft\MpApiTyres\Handlers\FetchJsonDistributorClassHandler;
use MpSoft\MpApiTyres\Handlers\FetchTyresHandler;
use MpSoft\MpApiTyres\Handlers\JsonCatalogHandler;
use MpSoft\MpApiTyres\Handlers\JsonDistributorsHandler;
use MpSoft\MpApiTyres\Handlers\SearchProductsCurlHandler;
use MpSoft\MpApiTyres\Handlers\UpdateCatalogHandler;
use MpSoft\MpApiTyres\Helpers\CreateInsertQueryFromJson;
use MpSoft\MpApiTyres\Helpers\CurlCatalogDownload;
use MpSoft\MpApiTyres\Helpers\ParseTyreCsvHelper;
use MpSoft\MpApiTyres\Models\ProductTyreModel;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

class AdminMpApiTyresController extends FrameworkBundleAdminController
{
    private $errors = [];
    public function index(Request $request)
    {
        $action = $request->request->get('action') ?? null;
        $params = $request->request->get('params') ?? '';
        if ($params) {
            $params = json_decode($params, true);
        }
        $response = [];

        if ($action) {
            if (method_exists($this, $action)) {
                $response = $this->$action($params);
            }

            return $this->json([
                'success' => true,
                'response' => $response ?? [],
                'errors' => $this->errors,
            ]);
        }

        return $this->render('@Modules/mpapityres/views/twig/AdminMpApiTyres/index.html.twig');
    }


    public function getDistributorsList($params)
    {
        $id = $params['id'] ?? null;

        $fetchDistributorListHandler = new FetchDistributorListHandler($id);
        $result = $fetchDistributorListHandler->fetchDistributorsList();

        return $result;
    }

    public function getFiltersJsonAction()
    {
        return $this->json([
            'filters' => $this->getFilters(),
            'sorters' => $this->getSorters(),
        ]);
    }

    protected function getFilters()
    {
        return file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/filters.json');
    }

    protected function getSorters()
    {
        return file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/sorters.json');
    }

    public function readCsvAction()
    {
        $start = microtime(true);
        $filename = ImportCsvController::CSV_FILE;
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
        $csvPath = ImportCsvController::CSV_FILE;
        $outputDir = ImportCsvController::CHUNK_PATH;
        $chunkSize = ImportCsvController::CHUNK_SIZE;

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

    public function loadPageAction(Request $request)
    {
        $type = $request->request->get('type');
        $page = $request->request->get('page') ?? 1;
        $limit = $request->request->get('limit') ?? 50;
        $orderBy = $request->request->get('orderBy') ?? 'id';
        $orderWay = $request->request->get('orderWay') ?? 'ASC';
        $id = $request->request->get('id') ?? null;

        return $this->json(['page' => $this->loadPage($type, $page, $limit, $orderBy, $orderWay, $id)]);
    }

    protected function loadPage($type, $page = 1, $limit = 50, $orderBy = 'id', $orderWay = 'ASC', $id = null)
    {
        $dotenv = new Dotenv();
        $dotenv->load(_PS_MODULE_DIR_ . 'mpapityres/.env');

        $apiTyres14Token = $_ENV['API_REST_TYRES14_TOKEN'];
        $apiProductsToken = $_ENV['API_REST_PRODUCTS_TOKEN'];
        $apiAlloysToken = $_ENV['API_REST_ALLOYS_TOKEN'];
        $apiWearPartsToken = $_ENV['API_REST_WEARPARTS_TOKEN'];

        $filters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/filters.json');
        $sorters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/sorters.json');

        if ($type === 'table') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\TableTyresController::index',
                [
                    'page' => $page,
                    'limit' => $limit,
                    'orderBy' => $orderBy,
                    'orderWay' => $orderWay,
                ]
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        if ($type == 'tyre-detail') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\TyreDetailController::index',
                [
                    'id' => $id,
                ]
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        if ($type == 'dashboard') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\DashboardController::index'
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        if ($type == 'importAPI') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\ImportCatalogFromTableController::index'
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        if ($type == 'importCSV') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\ImportCsvController::index'
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        if ($type == 'settings') {
            $response = $this->forward(
                'MpSoft\\MpApiTyres\\Controllers\\SettingsController::index'
            );
            $content = json_decode($response->getContent(), true);

            return $this->json($content);
        }

        switch ($type) {
            case 'dashboard':
                $totalProducts = rand(1, 1000);
                $totalCategories = rand(1, 100);
                $totalQuantity = rand(1, 10000);
                $latestProducts = [];
                $topSellingProducts = [];
                $salesTrend = [];
                $categoryDistribution = [];
                $salesTrend = [
                    'labels' => [],
                    'data' => [],
                ];
                $categoryDistribution = [
                    'labels' => [],
                    'data' => [],
                ];
                $data = [
                    'total_products' => $totalProducts,
                    'total_categories' => $totalCategories,
                    'total_quantity' => $totalQuantity,
                    'latest_products' => $latestProducts,
                    'top_selling_products' => $topSellingProducts,
                    'sales_trend' => $salesTrend,
                    'category_distribution' => $categoryDistribution,
                ];
                break;
            default:
                $data = [
                    'filters' => $filters,
                    'sorters' => $sorters,
                    'apiTyres14Token' => $apiTyres14Token,
                    'apiProductsToken' => $apiProductsToken,
                    'apiAlloysToken' => $apiAlloysToken,
                    'apiWearPartsToken' => $apiWearPartsToken,
                    'chunkFiles' => $this->getChunkFiles(),
                    'tyres' => $this->getTyres($page, $limit, $orderBy, $orderWay),
                    'page' => $page,
                    'total_pages' => $this->getTotalPages($limit),
                ];
                break;
        }


        return $this->renderView(
            "@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/{$type}.html.twig",
            $data
        );
    }

    protected function getTyres($page, $limit, $orderBy, $orderWay)
    {
        $productTyre = new ProductTyreModel();
        $rows = $productTyre->pagination($page, $limit, $orderBy, $orderWay);

        return $rows;
    }

    protected function getTotalPages($limit)
    {
        return 1;
    }

    protected function getChunkFiles()
    {
        $outputDir = ImportCsvController::CHUNK_PATH;
        return glob($outputDir . '/*.json');
    }

    public function downloadJsonAction(Request $request)
    {
        $filename = $request->query->get('file');
        $filePath = ImportCsvController::CHUNK_PATH . '/' . $filename;
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
        $filePath = ImportCsvController::CHUNK_PATH . '/' . $filename;
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

        $filePaths = glob(ImportCsvController::CHUNK_PATH . '/*.json');
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

    public function deleteDuplicateProductsAction()
    {
        $pfx = _DB_PREFIX_;
        $start = microtime(true);
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->get('doctrine.dbal.default_connection');
        $connection->beginTransaction();
        $totRows = $connection->executeQuery("SELECT COUNT(*) FROM {$pfx}product_tyre")->fetchOne();
        $deletedRows = 0;
        $getAllQuery = "SELECT * FROM {$pfx}product_tyre ORDER BY matchcode ASC";
        $getAllResult = $connection->executeQuery($getAllQuery);
        $tyres = $getAllResult->fetchAllAssociative();

        $currentMatchCode = null;
        $currentId = null;
        $currentPrice = null;
        $toDelete = [];

        foreach ($tyres as $tyre) {
            //Prima iterazione, imposto i parametri
            if (!$currentMatchCode) {
                $currentMatchCode = $tyre['matchcode'];
                $currentId = $tyre['id'];
                $currentPrice = $tyre['price_1'];
            } else {
                //Se matchcode è uguale, controllo il prezzo
                if ($currentMatchCode == $tyre['matchcode']) {
                    //Se il prezzo è maggiore, aggiungo l'id alla lista da cancellare
                    if ($tyre['price_1'] > $currentPrice) {
                        $toDelete[] = $tyre['id'];
                    } else {
                        //Altrimenti aggiungo l'id corrente alla lista da cancellare e aggiorno i parametri
                        $toDelete[] = $currentId;
                        $currentId = $tyre['id'];
                        $currentPrice = $tyre['price_1'];
                    }
                } else {
                    $currentMatchCode = $tyre['matchcode'];
                    $currentId = $tyre['id'];
                    $currentPrice = $tyre['price_1'];
                }
            }
        }

        if ($toDelete) {
            $connection->executeStatement("DELETE FROM {$pfx}product_tyre WHERE id IN (" . implode(',', $toDelete) . ")");
            $deletedRows = count($toDelete);
        }

        $connection->commit();
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'initial_rows' => $totRows,
            'deleted_rows' => $deletedRows,
            'remains_rows' => (int) $connection->executeQuery("SELECT COUNT(*) FROM {$pfx}product_tyre")->fetchOne(),
        ]);
    }

    /**
     * Restituisce un array con i nomi dei campi e le relative traduzioni in italiano
     * 
     * @return array
     */
    public static function getFeaturesList()
    {
        $translations = [
            'outer_diameter' => 'Diametro Esterno',
            'height' => 'Altezza',
            'width' => 'Larghezza',
            'tyre_type' => 'Tipo Pneumatico',
            'inner_diameter' => 'Diametro Interno',
            'profile' => 'Profilo',
            'usage' => 'Utilizzo',
            'type' => 'Tipo',
            'load_index' => 'Indice di Carico',
            'speed_index' => 'Indice di Velocità',
            'runflat' => 'Runflat',
            'ms' => 'MS (Mud & Snow)',
            'three_pmfs' => '3PMFS (Condizione di neve)',
            'da_decke' => 'Battistrada degradato',
            'energy_efficiency_index' => 'Indice Efficienza Energetica',
            'wet_grip_index' => 'Indice Aderenza sul Bagnato',
            'noise_index' => 'Indice Rumore',
            'noise_decible' => 'Rumore in Decibel',
            'vehicle_class' => 'Classe Veicolo',
            'ice_grip' => 'Grip su Ghiaccio',
            'dot' => 'DOT',
            'dot_year' => 'Anno DOT',
        ];

        return $translations;
    }

    public function createProductFeaturesAction()
    {
        $pfx = _DB_PREFIX_;
        $languages = \Language::getLanguages();
        $id_lang = (int) \Context::getContext()->language->id;
        $features = AdminMpApiTyresController::getFeaturesList();
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->get('doctrine.dbal.default_connection');
        $connection->beginTransaction();
        try {
            $featuresList = $connection->executeQuery(
                "
                    SELECT *
                    FROM {$pfx}feature_lang
                    WHERE id_lang = {$id_lang}
                    ORDER BY name ASC
                "
            )->fetchAllAssociative();
        } catch (\Throwable $th) {
            $featuresList = [];
        }
        $featuresList = array_column($featuresList, 'name');
        foreach ($features as $key => $value) {
            if (in_array($value, $featuresList)) {
                continue;
            }
            $feature = new \Feature();
            foreach ($languages as $language) {
                $feature->name[$language['id_lang']] = $value;
            }
            $feature->position = \Feature::getHigherPosition() + 1;
            $feature->save();
        }
        $connection->commit();

        $featuresList = $connection->executeQuery(
            "
                SELECT *
                FROM {$pfx}feature_lang
                WHERE id_lang = {$id_lang}
                ORDER BY name ASC
            "
        )->fetchAllAssociative();

        return $this->json([
            'success' => true,
            'features' => $featuresList,
        ]);
    }

    /**
     * Legge tutti i prodotti dalla tabella product_tyre e restituisce l'elenco
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateCatalogAction()
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->get('doctrine.dbal.default_connection');
        $connection->beginTransaction();
        $pfx = _DB_PREFIX_;
        $tyres = $connection->executeQuery("SELECT * FROM {$pfx}product_tyre")->fetchAllAssociative();

        return $this->json([
            'success' => true,
            'list' => $tyres,
        ]);
    }

    /**
     * Aggiorna i prodotti in base alla lista ricevuta
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateCatalogPartAction(Request $request)
    {
        $chunk = $request->request->get('chunk');
        $chunk = json_decode($chunk, true);
        $start = microtime(true);
        $updateCatalogHandler = new UpdateCatalogHandler();
        $updateCatalogHandler->updateCatalog($chunk);
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'new_rows' => $updateCatalogHandler->getNewRows(),
            'updated_rows' => $updateCatalogHandler->getUpdatedRows(),
        ]);
    }

    /**
     * Scarica le immagini
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function downloadImagesAction(Request $request)
    {
        $chunk = $request->request->get('chunk');
        $chunk = json_decode($chunk, true);
        $start = microtime(true);
        $updateCatalogHandler = new UpdateCatalogHandler();
        $updateCatalogHandler->downloadImages($chunk);
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'updated_rows' => $updateCatalogHandler->getUpdatedRows(),
            'errors' => $updateCatalogHandler->getErrors(),
        ]);
    }

    public function updateTyreTablesAction()
    {
        $error = '';
        $start = microtime(true);
        $sqlCreateTyreImages = CreateInsertQueryFromJson::buildNewTyreImagesQuery();
        $sqlUpdateTyreImages = CreateInsertQueryFromJson::buildUpdateTyreImagesQuery();
        $connection = $this->get('doctrine.dbal.default_connection');
        $connection->beginTransaction();
        try {
            $connection->executeStatement($sqlCreateTyreImages);
            $connection->executeStatement($sqlUpdateTyreImages);
            $connection->commit();
        } catch (\Throwable $th) {
            $error = $th->getMessage();
            $connection->rollBack();
        }
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'error' => $error,
        ]);
    }

    public function setSuppliersAction()
    {
        $this->errors = [];
        $brands = null;
        $productsList = null;
        $pfx = _DB_PREFIX_;
        $start = microtime(true);
        $connection = $this->get('doctrine.dbal.default_connection');

        try {
            $brands = $connection->executeQuery(
                "
                SELECT
                    brandname
                FROM {$pfx}product_tyre
                WHERE
                    brandname IS NOT NULL
                GROUP BY 
                    brandname
                ORDER BY
                    brandname ASC
            "
            )->fetchAllAssociative();

            $productsList = $connection->executeQuery(
                "
                SELECT
                    id_product,
                    reference
                FROM {$pfx}product
                WHERE
                    reference IS NOT NULL
                ORDER BY
                    reference ASC
            "
            )->fetchAllAssociative();
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
            return $this->json([
                'time' => 0,
                'errors' => $this->errors,
            ]);
        }

        if ($brands) {
            foreach ($brands as $brand) {
                $brandname = $brand['brandname'];
                try {
                    $connection->executeStatement(
                        "
                        INSERT INTO {$pfx}supplier
                        (
                            name,
                            date_add,
                            date_upd,
                            active
                        )
                        VALUES
                        (
                            :brandname,
                            NOW(),
                            NOW(),
                            1
                        )
                        ",
                        [
                            'brandname' => $brandname
                        ]
                    );
                } catch (\Throwable $th) {
                    $this->errors[] = $th->getMessage();
                }
            }
        }

        $brandList = $connection->executeQuery(
            "
            SELECT
                id_supplier,
                name
            FROM {$pfx}supplier
            WHERE
                active = 1
            ORDER BY
                name ASC
            "
        )->fetchAllAssociative();

        $brandProductList = $connection->executeQuery(
            "
            SELECT
                matchcode,
                brandname
            FROM
                {$pfx}product_tyre
            ORDER BY
                matchcode ASC
            "
        )->fetchAllAssociative();

        if ($brandProductList && $brandList && $productsList) {
            $productToId = [];
            $nameToId = [];
            $id_currency = (int) $this->getContext()->currency->id;
            foreach ($productsList as $item) {
                $productToId[$item['reference']] = $item['id_product'];
            }
            foreach ($brandList as $item) {
                $nameToId[$item['name']] = $item['id_supplier'];
            }

            foreach ($brandProductList as $brandProduct) {
                $reference = $brandProduct['matchcode'];
                $brandname = $brandProduct['brandname'];
                try {
                    $id_supplier = $nameToId[$brandname];
                    $id_product = $productToId[$reference];
                } catch (\Throwable $th) {
                    $this->errors[] = $th->getMessage();
                    continue;
                }
                if ($id_supplier && $id_product) {
                    try {
                        $addSupplier = $connection->executeStatement(
                            "
                            INSERT INTO {$pfx}product_supplier
                            (
                                id_product,
                                id_product_attribute,
                                id_supplier,
                                product_supplier_reference,
                                product_supplier_price_te,
                                id_currency
                            )
                            VALUES
                            (
                                :id_product,
                                :id_product_attribute,
                                :id_supplier,
                                :product_supplier_reference,
                                :product_supplier_price_te,
                                :id_currency
                            )
                            ",
                            [
                                'id_product' => $id_product,
                                'id_product_attribute' => 0,
                                'id_supplier' => $id_supplier,
                                'product_supplier_reference' => $reference,
                                'product_supplier_price_te' => 0,
                                'id_currency' => $id_currency
                            ]
                        );
                        if (!$addSupplier) {
                            $updateSupplier = $connection->executeStatement(
                                "
                                UPDATE {$pfx}product_supplier
                                SET
                                    id_supplier = :id_supplier,
                                    product_supplier_reference = :product_supplier_reference,
                                WHERE
                                    id_product = :id_product
                                    AND id_product_attribute = :id_product_attribute
                                ",
                                [
                                    'id_product' => $id_product,
                                    'id_product_attribute' => 0,
                                    'id_supplier' => $id_supplier,
                                    'product_supplier_reference' => $reference,
                                ]
                            );
                            if (!$updateSupplier) {
                                $this->errors[] = "Error updating supplier for product {$reference}";
                            }
                        }
                    } catch (\Throwable $th) {
                        $this->errors[] = $th->getMessage();
                    }
                }
            }
        }

        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'errors' => $this->errors,
        ]);
    }

    /**
     * Scarica il catalogo via API e lo inserisce inun file JSON
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function downloadApiAction(Request $request)
    {
        $start = microtime(true);

        $searchTerm = (string) $request->request->get('search');
        $limit = (int) $request->request->get('limit');
        $offset = (int) $request->request->get('offset');
        $minStock = (int) $request->request->get('minStock');
        $pfx = _DB_PREFIX_;

        if ($offset === 0) {
            $connection = $this->getDoctrine()->getConnection();
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_pricelist");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_manufacturer");
            $connection->executeStatement("TRUNCATE TABLE {$pfx}product_tyre_manufacturer_tyre");
        }

        $curlApiDownload = new SearchProductsCurlHandler();
        $result = $curlApiDownload->runWithFilters($searchTerm, $minStock, $offset, $limit);

        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'totalRows' => (new JsonCatalogHandler())->getRowsCount('tyres'),
            'result' => json_encode($result),
        ]);
    }

    public function importChunkTyresAction(Request $request)
    {
        $start = microtime(true);

        $chunk = $request->request->get('chunk');
        $chunk = json_decode($chunk, true);
        $productTyreApi = new ProductTyreApi();
        $updated = $productTyreApi->run($chunk);

        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'updated' => $updated,
        ]);
    }

    public function downloadFileAction()
    {
        $jsonCatalogHandler = new JsonCatalogHandler();
        $pfx = _DB_PREFIX_;

        try {
            $content = $jsonCatalogHandler->read('tyres');
        } catch (\Throwable $th) {
            return $this->json([
                'success' => false,
                'error' => $th->getMessage(),
            ]);
        }

        foreach ($content as $key => $item) {
            $productTyreApi = new ProductTyreApi();
            $productTyreApi->run($item);
        }

        return $this->json([
            'success' => true,
        ]);
    }

    public function fetchTyresAction(Request $request)
    {
        $start = microtime(true);
        $term = $request->request->get('term') ?? "%";
        $offset = $request->request->get('offset') ?? 0;
        $limit = $request->request->get('limit') ?? 500;
        $minStock = $request->request->get('minStock') ?? 4;
        $filters = $request->request->get('filters') ?? [];
        $fetchTyresHandler = new FetchTyresHandler($term, $offset, $limit, $minStock, $filters);

        $result = $fetchTyresHandler->getTyresResult();

        $elapsed = microtime(true) - $start;

        if (isset($result['error'])) {
            return $this->json([
                'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
                'offset' => 0,
                'rows' => json_encode([]),
                'error' => $result['error'],
            ]);
        }

        if ($result['offset'] == 0) {
            $last50 = $fetchTyresHandler->getLast50();
        }

        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'offset' => $result['offset'],
            'rows' => $result['rows'] > 0 ? [] : [],
            'last50' => $last50 ?? [],
        ]);
    }

    public function fetchJsonCatalogAction(Request $request)
    {
        $start = microtime(true);
        $this->errors = [];
        $action = $request->request->get('action') ?? '';
        $offset = $request->request->get('offset') ?? 0;
        $limit = $request->request->get('limit') ?? 500;

        if ($action && $action == 'saveManufacturers') {
            $manufacturers = json_decode($request->request->get('manufacturers'), true);
            $response = $this->saveManufacturers($manufacturers);

            $elapsed = microtime(true) - $start;

            return $this->json([
                'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
                'success' => true,
                'errors' => $response['errors'] ?? [],
                'updated' => $response['updated'] ?? 0,
                'total' => $response['total'] ?? 0,
            ]);
        }

        if ($action && $action == 'saveProducts') {
            $products = json_decode($request->request->get('products'), true);
            $total = count($products);
            $response = $this->saveProducts($products);

            $elapsed = microtime(true) - $start;

            return $this->json([
                'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
                'success' => true,
                'errors' => $response['errors'] ?? [],
                'updated' => $response['updated'] ?? 0,
                'total' => $total ?? 0,
            ]);
        }


        $handler = new JsonCatalogHandler();
        $result = [];
        $currentIndex = 0;
        $response = $handler->parseJsonArray('tyres', function ($item, $index) use (&$result, &$currentIndex) {
            // L'indice qui è relativo all'intero file, non all'offset
            $currentIndex = $index;
            $result[] = $item;
        }, $offset, $limit);
        $elapsed = microtime(true) - $start;

        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'offset' => $response['offset'],
            'limit' => $limit,
            'count' => $response['count'],
            'rows' => $result,
            'currentIndex' => $currentIndex,
        ]);
    }

    public function saveManufacturers($manufacturers)
    {
        $this->errors = [];
        $languages = \Language::getLanguages();
        $updated = 0;
        $total = count($manufacturers);

        foreach ($manufacturers as $manufacturerArray) {
            try {
                $id_manufacturer = (int) \Manufacturer::getIdByName($manufacturerArray['name']);
                $manufacturer = new \Manufacturer($id_manufacturer);
                // Se esiste già, non faccio nulla
                if (\Validate::isLoadedObject($manufacturer)) {
                    continue;
                }

                $manufacturer->name = strtoupper($manufacturerArray['name']);
                $manufacturer->active = 1;

                foreach ($languages as $language) {
                    $manufacturer->description[$language['id_lang']] = $manufacturerArray['description'];
                }

                $result = $manufacturer->save();

                if ($result) {
                    $updated++;
                    // Scarico l'immagine
                    $id_manufacturer = $manufacturer->id;

                    // Creo la directory se non esiste
                    $dir = _PS_MANU_IMG_DIR_;
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    // Scarico l'immagine dal web
                    $image_url = $manufacturerArray['image'];
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
                    $image_downloaded = false;

                    if (@file_put_contents($temp_name, file_get_contents($image_url))) {
                        $image_downloaded = true;
                    }

                    if ($image_downloaded) {
                        // Genero il nome dell'immagine
                        $image_name = $id_manufacturer . '.jpg';

                        // Salvo l'immagine nella directory dei manufacturer
                        if (\ImageManager::resize($temp_name, _PS_MANU_IMG_DIR_ . $image_name)) {
                            // Genero i thumbnails
                            $images_types = \ImageType::getImagesTypes('manufacturers');
                            foreach ($images_types as $image_type) {
                                \ImageManager::resize(
                                    $temp_name,
                                    _PS_MANU_IMG_DIR_ . $image_name . '-' . $image_type['name'] . '.jpg',
                                    $image_type['width'],
                                    $image_type['height']
                                );
                            }
                        }

                        // Elimino il file temporaneo
                        @unlink($temp_name);
                    }
                }
            } catch (\Throwable $th) {
                $this->errors[] = $th->getMessage();
            }
        }

        return [
            'errors' => $this->errors,
            'updated' => $updated,
            'total' => $total,
        ];
    }

    public function saveProducts($products)
    {
        $this->errors = [];
        $updated = 0;
        $total = count($products);

        $updateCatalog = new UpdateCatalog();

        foreach ($products as $product) {
            $result = $updateCatalog->updateProduct($product);
            if ($result['success']) {
                $updated++;
            }
        }

        return [
            'errors' => $this->errors,
            'updated' => $updated,
            'total' => $total,
        ];
    }

    public function setApiFiltersAction(Request $request)
    {
        $filter0 = $request->request->get('filter-0');
        $filter1 = $request->request->get('filter-1');
        $filter2 = $request->request->get('filter-2');
        $filter4 = $request->request->get('filter-4');
        $filter5 = $request->request->get('filter-5');
        $filter6 = $request->request->get('filter-6');

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

        return $this->json([
            'success' => $result,
        ]);
    }

    public function fetchJsonDistributorsAction()
    {
        $start = microtime(true);
        $JsonCatalogHandler = new JsonCatalogHandler();
        $last50 = $JsonCatalogHandler->read('tyres', -50);
        (new JsonDistributorsHandler())->delete();

        foreach ($last50 as $item) {
            try {
                $itemId = "T{$item['idT24']}";
            } catch (\Throwable $th) {
                $this->errors[] = $th->getMessage();
                continue;
            }
            $distributorsList = new FetchDistributorListHandler($itemId);
            $response = $distributorsList->fetchDistributorsList();
            if (isset($response['error']) && $response['error'] !== '') {
                $this->errors[] = $response['error'];
                continue;
            }
            $list = json_decode($response['list'], true);
            if ($list) {
                $list = array_values($list);
                $priceList = [
                    [
                        'idT24' => $item['idT24'],
                        'priceList' => $list,
                    ],
                ];
                $write = $distributorsList->write($priceList);
            }
        }

        $elapsed = microtime(true) - $start;

        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'count' => $response['result'],
            'errors' => $this->errors,
            'result ' => $write,
        ]);
    }

    public function fetchJsonDistributorClassAction(Request $request)
    {
        $start = microtime(true);
        $action = $request->request->get('action');
        $params = $request->request->get('params');
        $fetchJsonDistributorClassHandler = new FetchJsonDistributorClassHandler($action, $params);
        $result = $fetchJsonDistributorClassHandler->run();
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => ParseTyreCsvHelper::getHumanTiming($elapsed),
            'result' => $result,
        ]);
    }
}
