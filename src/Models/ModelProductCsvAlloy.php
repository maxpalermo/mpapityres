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

namespace MpSoft\MpApiTyres\Models;

use MpSoft\MpApiTyres\Catalog\CreatePFU;
use MpSoft\MpApiTyres\Helpers\TyreWeight;
use Configuration;
use Db;
use DbQuery;
use ImageManager;
use ImageType;
use Language;
use Manufacturer;
use Product;
use SpecificPrice;
use StockAvailable;
use Validate;

class ModelProductCsvAlloy extends \ObjectModel
{
    /**
     * Chiave primaria numerica
     * (colonna `id` nella tabella, gestita da ObjectModel tramite la proprietà $id)
     */

    /**
     * @var string
     */
    public $article_id;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $description_2;

    /**
     * @var float|string
     */
    public $price;

    /**
     * @var float|string
     */
    public $price_4;

    /**
     * @var float|string
     */
    public $avg_price;

    /**
     * @var float|string
     */
    public $rvo_price;

    /**
     * @var int
     */
    public $availability;

    /**
     * @var string
     */
    public $manufacturer;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $type_color;

    /**
     * @var string
     */
    public $type_design;

    /**
     * @var int
     */
    public $number_of_holes;

    /**
     * @var float
     */
    public $hole_circle;

    /**
     * @var float
     */
    public $hole_circle2;

    /**
     * @var float
     */
    public $hole_circle3;

    /**
     * @var float
     */
    public $width;

    /**
     * @var int
     */
    public $diameter;

    /**
     * @var float
     */
    public $offset_from;

    /**
     * @var float
     */
    public $offset_till;

    /**
     * @var float
     */
    public $centre_bore;

    /**
     * @var int
     */
    public $centre_bore_type;

    /**
     * @var array|string|null
     */
    public $images;

    /**
     * @var string
     */
    public $factory_number;

    /**
     * @var string
     */
    public $expected_delivery_date;

    /**
     * @var string
     */
    public $manufacturer_description;

    /**
     * @var string
     */
    public $date_add;

    /**
     * @var string
     */
    public $date_upd;

    public const CSV_URL = 'https://tyre24.alzura.com//it/it/export/download-via-token/token/{token}/accountId/{accountId}/t/2/c/35/';

    public static $definition = [
        'table' => 'product_csv',
        'primary' => 'id',
        'fields' => [
            'article_id' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'description' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'description_2' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'price_4' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'avg_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'rvo_price' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'availability' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'manufacturer' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 2],
            'type_color' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'type_design' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'number_of_holes' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'hole_circle' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'hole_circle2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'hole_circle3' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'width' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'diameter' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'offset_from' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'offset_till' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'centre_bore' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'centre_bore_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'images' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'factory_number' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'expected_delivery_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'manufacturer_description' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 1024],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $context = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $context);
        if (!is_array($this->image_link)) {
            $this->image_link = json_decode($this->image_link, true);
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if (is_array($this->images)) {
            $this->images = json_encode($this->images);
        }

        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        if (is_array($this->images)) {
            $this->images = json_encode($this->images);
        }
        return parent::update($null_values);
    }

    public static function checkTableExists()
    {
        $pfx = _DB_PREFIX_;
        $db_name = _DB_NAME_;
        $table = self::$definition['table'];

        $QUERY = "
            SELECT COUNT(*) AS table_exists
            FROM information_schema.tables
            WHERE table_schema = '{$db_name}'
            AND table_name = '{$pfx}{$table}';
        ";

        return (int) Db::getInstance()->getValue($QUERY);
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $table = self::$definition['table'];
        $QUERY = "
            -- Creazione tabella per cerchi in lega (alloy wheels)
            CREATE TABLE IF NOT EXISTS {$pfx}{$table} (
                -- Identificativo unico auto-incrementale
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'ID unico del prodotto',
                
                -- Informazioni prodotto
                article_id VARCHAR(255) NOT NULL COMMENT 'Codice articolo univoco',
                description VARCHAR(255) COMMENT 'Descrizione principale',
                description_2 VARCHAR(255) COMMENT 'Descrizione secondaria',
                manufacturer VARCHAR(255) COMMENT 'Marca produttore',
                factory_number VARCHAR(255) COMMENT 'Numero di fabbrica',
                
                -- Prezzi
                price DECIMAL(20,6) COMMENT 'Prezzo singolo pezzo',
                price_4 DECIMAL(20,6) COMMENT 'Prezzo per 4 pezzi',
                avg_price DECIMAL(20,6) COMMENT 'Prezzo medio',
                rvo_price DECIMAL(20,6) COMMENT 'Prezzo RVO/Tyre.one',
                
                -- Disponibilità
                availability INT UNSIGNED DEFAULT 0 COMMENT 'Quantità disponibile in magazzino',
                expected_delivery_date DATE COMMENT 'Data di consegna prevista (YYYY-MM-DD)',
                
                -- Caratteristiche tecniche cerchio
                type VARCHAR(255) COMMENT 'Tipo cerchio (es: in lega, forgiati)',
                type_color VARCHAR(255) COMMENT 'Colore del cerchio',
                type_design VARCHAR(255) COMMENT 'Design del cerchio',
                number_of_holes INT UNSIGNED COMMENT 'Numero di fori per i bulloni',
                hole_circle FLOAT COMMENT 'Diametro cerchio fori (PCD)',
                hole_circle2 FLOAT COMMENT 'Secondo diametro cerchio fori (se presente)',
                hole_circle3 FLOAT COMMENT 'Terzo diametro cerchio fori (se presente)',
                width FLOAT COMMENT 'Larghezza del cerchio in pollici',
                diameter INT UNSIGNED COMMENT 'Diametro del cerchio in pollici',
                offset_from FLOAT COMMENT 'Offset minimo (ET)',
                offset_till FLOAT COMMENT 'Offset massimo (ET)',
                centre_bore FLOAT COMMENT 'Diametro foro centrale (CB)',
                centre_bore_type INT UNSIGNED COMMENT 'Tipo foro centrale (0=fisso, 1=adattabile)',
                
                -- Immagini e documentazione
                images JSON COMMENT 'URL immagini in formato JSON',
                manufacturer_description TEXT COMMENT 'Descrizione tecnica del produttore',
                
                -- Timestamps per tracciamento
                date_add DATETIME COMMENT 'Data di creazione record',
                date_upd DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP NULL COMMENT 'Data ultima modifica',
                
                -- Chiavi e indici per ottimizzazione query
                UNIQUE KEY uk_article_id (article_id)
            ) ENGINE={$engine} 
            COMMENT='Tabella cerchi in lega per autoveicoli';
        ";

        return Db::getInstance()->execute($QUERY);
    }

    public static function readCsvStream($path, $delimiter = '|')
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossibile aprire il file CSV: {$path}");
        }

        // Prima riga = header
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            return;
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_slice($row, 0, count($header));
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }

            yield array_combine($header, $row);
        }

        fclose($handle);
    }

    public static function insertData($csvPath = null, $delimiter = '|')
    {
        $errors = [];
        $inserted = 0;
        $skipExists = (int) Configuration::get('MPAPITYRES_SKIP_EXISTS');
        $skipped = 0;
        $updated = 0;
        $start = microtime(true);

        if (!$csvPath) {
            $token = Configuration::get('MPAPITYRES_ALLOY_TOKEN');
            $accountId = Configuration::get('MPAPITYRES_ACCOUNT_ID');
            $csvPath = str_replace(['{token}', '{accountId}'], [$token, $accountId], self::CSV_URL);
        }

        foreach (self::readCsvStream($csvPath, $delimiter) as $row) {
            /*
             * Cambio la dimensione dell'immagine del prodotto da -w[d+]-h[d+] a -w800-h800
             */
            $images = [];
            foreach ($row as $column => $value) {
                if (preg_match('/^image_link_[0-9]+/', $column)) {
                    if (strpos($value, '-w') !== false) {
                        $value = preg_replace('/-w\d+-h\d+/', '-w400-h400', $value);
                    } else {
                        $value = '404.gif';
                    }
                    $images[] = $value;
                }
            }
            $row['images'] = $images;

            /** Controllo se il campo expected-delivery-date è valido, se no salto l'inserimento */
            if (!Validate::isDate($row['expected_delivery_date'])) {
                echo "Data di consegna non valida per il prodotto {$row['id']}\n";
                continue;
            }

            $product = new ModelProductAlloy($row['id']);
            if (Validate::isLoadedObject($product) && $skipExists) {
                echo "\n{$row['id']} esiste già. Saltato.\n";
                $skipped++;
                continue;
            }

            $product->article_id = $row['article_id'];
            $product->description = $row['description'];
            $product->description_2 = $row['description_2'];
            $product->manufacturer = $row['manufacturer'];
            $product->factory_number = $row['factory_number'];
            $product->price = (float) $row['price'];
            $product->price_4 = (float) $row['price_4'];
            $product->avg_price = (float) $row['avg_price'];
            $product->rvo_price = $row['rvo_price'];
            $product->availability = (int) $row['availability'];
            $product->expected_delivery_date = $row['expected_delivery_date'];
            $product->type = $row['type'];
            $product->type_color = $row['type_color'];
            $product->type_design = $row['type_design'];
            $product->number_of_holes = (int) $row['number_of_holes'];
            $product->hole_circle = (float) $row['hole_circle'];
            $product->hole_circle2 = (float) $row['hole_circle2'];
            $product->hole_circle3 = (float) $row['hole_circle3'];
            $product->width = (float) $row['width'];
            $product->diameter = (int) $row['diameter'];
            $product->offset_from = (float) $row['offset_from'];
            $product->offset_till = (float) $row['offset_till'];
            $product->centre_bore = (float) $row['centre_bore'];
            $product->centre_bore_type = (int) $row['centre_bore_type'];
            $product->images = $row['images'];
            $product->manufacturer_description = $row['manufacturer_description'];
            $product->date_add = date('Y-m-d H:i:s');
            $product->date_upd = null;

            try {
                if (Validate::isLoadedObject($product)) {
                    $product->date_upd = date('Y-m-d H:i:s');
                    $product->update(true);
                    $updated++;
                    echo "{$row['id']}\t\taggiornato\n";
                } else {
                    $product->force_id = true;
                    $product->id = $row['id'];
                    $product->add(false, true);
                    echo "{$row['id']}\t\tinserito\n";
                }
                $inserted++;
            } catch (\Throwable $th) {
                $errors[] = "{$row['id']}: {$th->getMessage()}";
            }
        }

        $time = self::humanReadableSeconds(microtime(true) - $start);

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'updated' => $updated,
            'errors' => $errors,
            'time' => $time,
        ];
    }

    public static function downloadCsv($url = null)
    {
        $start = microtime(true);

        if (!$url) {
            $token = Configuration::get('MPAPITYRES_ALLOY_TOKEN');
            $accountId = Configuration::get('MPAPITYRES_ACCOUNT_ID');
            $url = str_replace(['{token}', '{accountId}'], [$token, $accountId], self::CSV_URL);
        }

        // Scarico il file ZIP tramite guzzleHTTP
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $body = $response->getBody();
        $file = $body->getContents();
        $downloadFolder = _PS_ROOT_DIR_ . '/download/csv/';

        // Salvo il file ZIP in /download/csv/
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

        $time = self::humanReadableSeconds(microtime(true) - $start);

        return [
            'success' => true,
            'filename' => $filename,
            'time' => $time,
        ];
    }

    public static function updateCatalog()
    {
        $start = microtime(true);

        echo "Lettura dei filtri di importazione:\n";
        $filter_ms = (int) Configuration::get('MPAPITYRES_FILTER_MS');
        $filter_runflat = (int) Configuration::get('MPAPITYRES_FILTER_RUNFLAT');
        $filter_da_decke = (int) Configuration::get('MPAPITYRES_FILTER_DA_DECKE');
        $filter_demo = (int) Configuration::get('MPAPITYRES_FILTER_DEMO');
        $filter_dot = (int) Configuration::get('MPAPITYRES_FILTER_DOT');
        $filter_3pmfs = (int) Configuration::get('MPAPITYRES_FILTER_3PMFS');
        $filter_quantity_min = (int) Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN');
        $id_category_associated = (int) Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED');
        $load_amount = (float) Configuration::get('MPAPITYRES_LOAD_AMOUNT');
        $load_percentage = (float) Configuration::get('MPAPITYRES_LOAD_PERCENTAGE');
        $delivery_delay_days = (int) Configuration::get('MPAPITYRES_DELIVERY_DELAY_DAYS');

        echo "Filtro MS: {$filter_ms}\n";
        echo "Filtro RUNFLAT: {$filter_runflat}\n";
        echo "Filtro DA_DECKE: {$filter_da_decke}\n";
        echo "Filtro DEMO: {$filter_demo}\n";
        echo "Filtro DOT: {$filter_dot}\n";
        echo "Filtro 3PMFS: {$filter_3pmfs}\n";
        echo "Filtro QUANTITY_MIN: {$filter_quantity_min}\n";
        echo "Ricarico EUR: {$load_amount}\n";
        echo "Ricarico percentuale: {$load_percentage}%\n";
        echo "Giorni di consegna aggiuntivi: {$delivery_delay_days}\n";

        if ($id_category_associated) {
            /** @var \Category $category */
            $category = new \Category($id_category_associated, (int) \Context::getContext()->language->id);
            if (!$category->id) {
                echo "ERRORE: Categoria con ID {$id_category_associated} non trovata\n";
                return [
                    'success' => false,
                    'time' => 0,
                ];
            }
            echo "Associo i prodotti alla categoria {$category->name}\n";
        } else {
            echo "Non è stata selezionata nessuna categoria per l'associazione\n";
            return [
                'success' => false,
                'time' => 0,
            ];
        }

        echo "Leggo i dati dalla tabella CSV\n";
        $totalRows = self::countProducts();
        echo "Totale righe nella tabella CSV: {$totalRows}\n";

        $rows = self::getCsvList();
        echo 'Trovate ' . count($rows) . " righe nella tabella CSV su {$totalRows} totali\n";

        self::doUpdateCatalog($rows);

        $end = microtime(true);

        $time = self::humanReadableSeconds($end - $start);
        echo "\n=================================================";
        echo "\n===== Tempo totale elaborazione: {$time} =====";
        echo "\n=================================================\n";

        return [
            'success' => true,
            'time' => $time,
        ];
    }

    public static function doUpdateCatalog($rows)
    {
        $params = [
            'id_default_category' => (int) Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED'),
            'load_amount' => (float) Configuration::get('MPAPITYRES_LOAD_AMOUNT'),
            'load_percentage' => (float) Configuration::get('MPAPITYRES_LOAD_PERCENTAGE'),
            'delivery_delay_days' => (int) Configuration::get('MPAPITYRES_DELIVERY_DELAY_DAYS'),
        ];

        foreach ($rows as $row) {
            self::updateProduct($row);
        }
    }

    public static function humanReadableSeconds($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
    }

    protected static function getCsvList()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $where = self::getWhereFilter();
        $query
            ->select('*')
            ->from(self::$definition['table'])
            ->where($where)
            ->orderBy(self::$definition['primary'] . ' ASC');

        echo '\n=================================================';
        echo '\nQUERY DI RICERCA: ' . $query->build();
        echo '\n=================================================\n';

        return $db->executeS($query);
    }

    /**
     * Aggiorna o crea un prodotto in PrestaShop
     *
     * @param array $product Dati del prodotto
     * @param array $params Parametri aggiuntivi
     * @return bool
     */
    protected static function updateProduct($product)
    {
        $id_tax_rules_group = (int) Configuration::get('MPAPITYRES_TAX_RULES_GROUP_ID');
        $id_default_category = (int) Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED');
        $load_amount = (float) Configuration::get('MPAPITYRES_LOAD_AMOUNT');
        $load_percentage = (float) Configuration::get('MPAPITYRES_LOAD_PERCENTAGE');
        $id_product = (int) $product['id'];
        $reference = $product['matchcode'];
        $price_1_loaded = (float) self::calcLoadPrice($product['price_1'], $load_amount, $load_percentage);
        $price_4_loaded = (float) self::calcLoadPrice($product['price_4'], $load_amount, $load_percentage);
        $delivery_delay_days = (int) Configuration::get('MPAPITYRES_DELIVERY_DELAY_DAYS');

        if (!$product['matchcode']) {
            echo "\n\t Non ho il matchcode per il prodotto {$product['id']}, importazione saltata.";
            return false;
        }

        if (!trim($product['articlename'])) {
            echo "\n\t Non ho il nome per il prodotto {$product['id']}, importazione saltata.";
            return false;
        }

        // Aggiorno la scadenza
        $deliveryDate = $product['expected_delivery_date'] ?? '';
        if (!Validate::isDateFormat($deliveryDate)) {
            echo "\n\t Data di consegna non valida per il prodotto {$id_product}: {$deliveryDate}";
            return false;
        }
        $delayDate = self::getDeliveryDateDelay($deliveryDate, $delivery_delay_days);

        try {
            $db = Db::getInstance();

            $productObj = new Product($id_product, false);

            if (Validate::isLoadedObject($productObj)) {
                if ($product['price_1'] == 0) {
                    $productObj->active = 0;
                    $productObj->price = 0;
                    $productObj->save();

                    self::removeQuantityPrice($id_product, 4);

                    echo "\n\t Prodotto {$id_product} disattivato e prezzo impostato a 0";
                    return true;
                }

                $productObj->name = self::createMultiLangField($product['articlename']);
                $productObj->description_short = self::createDescription($product);
                $productObj->link_rewrite = self::createMultiLangField(
                    \Tools::str2url($product['articlename'] ?? 'product-' . $reference)
                );

                if ($productObj->price != $price_1_loaded) {
                    echo "\n\t Prezzo prodotto {$id_product} aggiornato da {$productObj->price} a {$price_1_loaded}";
                    $db->update('product', ['price' => $price_1_loaded], 'id_product = ' . $id_product);
                    $db->update('product_shop', ['price' => $price_1_loaded], 'id_product = ' . $id_product);
                }

                // Aggiorno il prezzo del set (4 prodotti)
                if ($price_4_loaded && $price_1_loaded != $price_4_loaded) {
                    $price_4 = (float) $price_4_loaded;
                    self::addQuantityPrice($id_product, 4, $price_4);
                } elseif ($price_4_loaded && $price_1_loaded == $price_4_loaded) {
                    self::removeQuantityPrice($id_product, 4);
                }

                if (StockAvailable::getQuantityAvailableByProduct($id_product, 0) != $product['availability']) {
                    echo "\n\t Quantità prodotto {$id_product} aggiornata da {$productObj->quantity} a {$product['availability']}";
                    StockAvailable::setQuantity($id_product, 0, (int) $product['availability']);
                }

                // Aggiorno il PFU
                $createPfu = new CreatePFU();
                $createPfu->setProductToPfu($id_product);

                // Controllo che non abbia immagini
                $imageCover = \Image::getCover($id_product);
                if (!$imageCover) {
                    // Gestione delle immagini
                    if (!empty($product['image'])) {
                        self::addProductImageStatic($id_product, $product['image']);
                    }

                    // Gestione dell'immagine del label
                    if (!empty($product['tyrelabel_link'])) {
                        self::addProductImageStatic($id_product, $product['tyrelabel_link']);
                    }
                }

                // Aggiorno la scadenza
                self::updateDeliveryDate($id_product, $delayDate);

                return true;
            }

            // Controllo che il prezzo non sia Zero
            if ($product['price_1'] == 0) {
                echo "\n\t Prezzo prodotto {$id_product} non valido. Importazione saltata.";
                return false;
            }

            // Creo o aggiorno il prodotto
            $id_product = (int) $product['id'];
            $prestashopProduct = new Product($id_product);

            // Dati base del prodotto
            $prestashopProduct->force_id = true;
            $prestashopProduct->id = $id_product;
            $prestashopProduct->reference = $reference;
            $prestashopProduct->name = self::createMultiLangField($product['articlename'] ?? '');
            $prestashopProduct->description_short = self::createDescription($product);
            $prestashopProduct->link_rewrite = self::createMultiLangField(
                \Tools::str2url($product['articlename'] ?? 'product-' . $reference)
            );

            // Prezzi e tasse
            $prestashopProduct->ean13 = (string) ($product['ean'] ?? '');
            $prestashopProduct->price = (float) $price_1_loaded;
            $prestashopProduct->wholesale_price = (float) ($product['wholesale_price'] ?? 0);
            $prestashopProduct->id_tax_rules_group = (int) ($id_tax_rules_group);

            // Stock
            $prestashopProduct->quantity = (int) ($product['availability'] ?? 0);
            $prestashopProduct->minimal_quantity = (int) ($product['minimal_quantity'] ?? 1);
            $prestashopProduct->low_stock_threshold = (int) ($product['low_stock_threshold'] ?? 0);
            $prestashopProduct->low_stock_alert = (bool) ($product['low_stock_alert'] ?? false);

            // Stato e visibilità
            $prestashopProduct->active = (bool) ($product['active'] ?? true);
            $prestashopProduct->visibility = $product['visibility'] ?? 'both';  // both, catalog, search, none
            $prestashopProduct->available_for_order = (bool) ($product['available_for_order'] ?? true);
            $prestashopProduct->show_price = (bool) ($product['show_price'] ?? true);

            // Categorie
            $prestashopProduct->id_category_default = $id_default_category;

            // Creo o Restituisco il produttore
            $manufacturer = [
                'name' => $product['brandname'] ?? '',
                'description' => $product['manufacturer_description'] ?? '',
                'image' => $product['manufacturer_image'] ?? '',
            ];
            $id_manufacturer = self::getOrCreateManufacturer($manufacturer);
            $prestashopProduct->id_manufacturer = $id_manufacturer;

            // Dimensioni e peso
            $prestashopProduct->width = (int) ($product['width'] ?? 0);
            $prestashopProduct->height = (int) ($product['height'] ?? 0);
            $prestashopProduct->depth = (int) ($product['inner_diameter'] ?? 0);
            $tyre = "{$prestashopProduct->width}/{$prestashopProduct->height}/{$prestashopProduct->depth}";
            $tyreWeight = TyreWeight::calcByCode($tyre);
            $prestashopProduct->weight = (float) ($tyreWeight ?? 0);

            if ($tyreWeight == 0) {
                echo "\n\t Pneumatico {$reference}: Peso non valido. Importazione saltata.";
                return false;
            }

            // Meta informazioni per SEO
            $prestashopProduct->meta_title = self::createMultiLangField($product['meta_title'] ?? '');
            $prestashopProduct->meta_description = self::createMultiLangField($product['meta_description'] ?? '');
            $prestashopProduct->meta_keywords = self::createMultiLangField($product['meta_keywords'] ?? '');

            // Salvo il prodotto
            try {
                $result = $prestashopProduct->add();
            } catch (\Throwable $th) {
                echo "\n\t Prodotto ({$product['id']}) - {$reference}: Errore {$th->getMessage()}";

                return false;
            }

            if (!$result) {
                echo "\n\t Errore durante il salvataggio del prodotto ({$product['id']} - {$reference})";

                return false;
            }

            $id_product = (int) $prestashopProduct->id;

            // Associo le categorie
            $categories = [$id_default_category];
            if (!empty($product['categories']) && is_array($product['categories'])) {
                $categories = array_merge($categories, $product['categories']);
            }
            $prestashopProduct->updateCategories(array_unique($categories));

            $product['features'] = [
                [
                    'name' => 'Uso',
                    'value' => $product['usage'] ?? null,
                ],
                [
                    'name' => 'M+S',
                    'value' => $product['ms'] ?? null,
                ],
                [
                    'name' => 'Tipo pneumatico',
                    'value' => $product['tyre_type'] ?? null,
                ],
                [
                    'name' => 'Stagione',
                    'value' => $product['type'] ?? null,
                ],
                [
                    'name' => 'Indice di velocita',
                    'value' => $product['speed_index'] ?? null,
                ],
                [
                    'name' => 'Indice di carico',
                    'value' => $product['load_index'] ?? null,
                ],
                [
                    'name' => 'Indice tenuta bagnato',
                    'value' => $product['wet_grip_index'] ?? null,
                ],
                [
                    'name' => 'Indice efficienza energia',
                    'value' => $product['energy_efficiency_index'] ?? null,
                ],
                [
                    'name' => 'Indice rumore',
                    'value' => $product['noise_index'] ?? null,
                ],
                [
                    // ATTENZIONE NEL FILE CSV È SCRITTO MALE, !NOISE_DECIBLE!
                    'name' => 'Livello rumore',
                    'value' => $product['noise_decible'] ?? null,
                ],
                [
                    'name' => 'Classe Veicolo',
                    'value' => $product['vehicle_class'] ?? null,
                ],
                [
                    'name' => '3PMSF',
                    'value' => $product['three_pmsf'] ?? null,
                ],
                [
                    'name' => 'Runflat',
                    'value' => $product['runflat'] ?? null,
                ],
                [
                    'name' => 'Da Decke',
                    'value' => $product['da_decke'] ?? null,
                ],
                [
                    'name' => 'Tenuta su ghiaccio',
                    'value' => $product['ice_grip'] ?? null,
                ],
                [
                    'name' => 'Demo',
                    'value' => $product['demo'] ?? null,
                ],
                [
                    'name' => 'DOT',
                    'value' => $product['dot'] ?? null,
                ],
            ];
            // Gestione delle caratteristiche
            if (!empty($product['features']) && is_array($product['features'])) {
                foreach ($product['features'] as $feature) {
                    if (empty($feature['name']) || empty($feature['value'])) {
                        continue;
                    }

                    // Cerco o creo la caratteristica
                    $id_feature = self::getOrCreateFeature($feature['name']);

                    // Cerco o creo il valore della caratteristica
                    $id_feature_value = self::getOrCreateFeatureValue($id_feature, $feature['value']);

                    // Associo la caratteristica al prodotto
                    $prestashopProduct->addFeaturesToDB($id_feature, $id_feature_value);
                }
            }

            // Gestione delle immagini
            if (!empty($product['image'])) {
                self::addProductImageStatic($id_product, $product['image']);
            }

            // Gestione dell'immagine del label
            if (!empty($product['tyrelabel_link'])) {
                self::addProductImageStatic($id_product, $product['tyrelabel_link']);
            }

            // Aggiorno la quantità di stock
            if (isset($product['availability'])) {
                StockAvailable::setQuantity($id_product, 0, (int) $product['availability']);
            }

            // Imposto lo specific price per 4 pneumatici
            if ($price_4_loaded > 0 && $price_1_loaded != $price_4_loaded) {
                self::addQuantityPrice($id_product, 4, (float) $price_4_loaded);
            }

            // Aggiorno la scadenza
            self::updateDeliveryDate($id_product, $delayDate);

            // Aggiorno il PFU
            $createPfu = new CreatePFU();
            $createPfu->setProductToPfu($id_product);

            echo "\n\t È stato aggiunto un nuovo prodotto: {$id_product} EAN: {$product['ean']}";

            return true;
        } catch (\Exception $e) {
            echo "\n\t Errore durante l'aggiornamento del prodotto: " . $e->getMessage();

            return false;
        }
    }

    public static function calcLoadPrice($price, $loadAmount, $loadPerc)
    {
        if ($loadAmount > 0) {
            $price += $loadAmount;
        }

        if ($loadPerc > 0) {
            $price += $price * ($loadPerc / 100);
        }

        return $price;
    }

    /**
     * Aggiunge uno specific price per quantità >= 4
     * @param int $id_product - ID prodotto
     * @param float $reduction - Importo riduzione (es: 0.10 per 10%)
     * @param string $reduction_type - 'percentage' o 'amount'
     * @return bool
     */
    protected static function addQuantityPrice($id_product, $quantity, $price)
    {
        $db = Db::getInstance();
        $id_product = (int) $id_product;
        $quantity = (int) $quantity;
        $price = (float) $price;

        // Verifica se il prodotto esiste
        if (!Product::existsInDatabase($id_product, 'product')) {
            echo "\n\t Prodotto {$id_product} non trovato.";
        }

        // Verifica se esiste già uno specific price per quantità >= 4
        $pfx = _DB_PREFIX_;
        $existingPrice = $db->getValue("
            SELECT
                id_specific_price 
            FROM
                {$pfx}specific_price 
            WHERE
                id_product = {$id_product} 
                AND from_quantity >= {$quantity}
                AND id_shop = 0 
                AND id_currency = 0 
                AND id_country = 0 
                AND id_group = 0
        ");

        if ($existingPrice) {
            if ($price == 0) {
                $specificPrice = new SpecificPrice($existingPrice);
                if (Validate::isLoadedObject($specificPrice)) {
                    $specificPrice->delete();
                }

                echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato eliminato.";
            } else {
                $price = number_format($price, 6);
                $db->update(
                    'specific_price',
                    [
                        'price' => $price,
                    ],
                    "id_specific_price = {$existingPrice}"
                );

                echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato aggiornato a {$price}.";
            }

            return true;
        }

        if (!$price) {
            echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici non è stato aggiunto perché il prezzo è 0.";
            return false;
        }

        // Crea il nuovo specific price
        $specificPrice = new SpecificPrice();
        $specificPrice->id_product = $id_product;
        $specificPrice->id_shop = 0;
        $specificPrice->id_currency = 0;
        $specificPrice->id_country = 0;
        $specificPrice->id_group = 0;
        $specificPrice->id_customer = 0;
        $specificPrice->from_quantity = $quantity;
        $specificPrice->price = $price;
        $specificPrice->reduction_type = 'amount';
        $specificPrice->reduction = 0;
        $specificPrice->from = '0000-00-00 00:00:00';
        $specificPrice->to = '0000-00-00 00:00:00';

        $result = $specificPrice->add();

        if ($result) {
            echo "\n\t {$id_product}: Il prezzo specifico {$price} per {$quantity} pneumatici è stato creato.";
        }

        return $result;
    }

    /**
     * Rimuove il prezzo specifico per quantit�
     * @param int $id_product ID del prodotto
     * @param int $quantity Quantit�
     * @return void
     */
    public static function removeQuantityPrice($id_product, $quantity)
    {
        $db = Db::getInstance();
        $id_product = (int) $id_product;
        $quantity = (int) $quantity;

        // Verifica se il prodotto esiste
        if (!Product::existsInDatabase($id_product, 'product')) {
            echo "\n\t Prodotto {$id_product} non trovato.";
        }

        // Verifica se esiste già uno specific price per quantità >= 4
        $pfx = _DB_PREFIX_;
        $existingPrice = $db->getValue("SELECT id_specific_price FROM {$pfx}specific_price WHERE id_product = {$id_product} AND from_quantity >= {$quantity} AND id_shop = 0 AND id_currency = 0 AND id_country = 0 AND id_group = 0");

        if ($existingPrice) {
            $specificPrice = new SpecificPrice($existingPrice);
            if (Validate::isLoadedObject($specificPrice)) {
                $specificPrice->delete();
            }

            echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato eliminato perchè uguale al prezzo singolo.";
        }
    }

    /**
     * Aggiunge un'immagine a un prodotto
     *
     * @param int $id_product ID del prodotto
     * @param string $image_url URL dell'immagine
     * @return array {status: string, id_product: int, url: string, httpCode: int, contentType: string, message: string}
     */
    public static function addProductImageStatic($id_product, $image_url)
    {
        $hasCover = (int) self::hasCover($id_product);

        // Scarico l'immagine dal web
        // $imageCurl = self::downloadImageFromUrlStatic($image_url);
        error_clear_last();
        $imageContent = self::guzzleDownload($image_url);

        if ($imageContent === false) {
            $error = error_get_last();
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $error['type'] ?? 'sconosciuto',
                'error_message' => $error['message'] ?? 'errore inatteso',
                'file' => $error['file'] ?? '--',
                'line' => $error['line'] ?? '--',
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => "Immagine non scaricata: {$image_url}",
            ];
        }

        try {
            // Creo un'istanza dell'immagine
            $image = new \Image();
            $image->id_product = (int) $id_product;
            $image->position = \Image::getHighestPosition($id_product) + 1;
            $image->cover = !$hasCover;

            // Salvo l'immagine nel database
            Db::getInstance()->displayError(true);
            if (!$image->add()) {
                Db::getInstance()->displayError(true);
                return [
                    'status' => 'ERROR',
                    'id_product' => $id_product,
                    'url' => $image_url,
                    'error' => Db::getInstance()->getNumberError(),
                    'error_message' => Db::getInstance()->getMsgError(),
                    'httpCode' => 500,
                    'contentType' => 'text/plain',
                    'message' => 'Classe Image->add() ' . Db::getInstance()->getMsgError(),
                ];
            }

            if ($imageContent) {
                // Salvo il contenuto dell'immagine nella cartella
                $image_path = $image->getPathForCreation();
                $imageContent = base64_decode($imageContent);
                $destOriginalImage = "{$image_path}.jpg";
                if (!file_put_contents($destOriginalImage, $imageContent)) {
                    $error = error_get_last();
                    $errorCode = $error['type'] ?? 0;
                    $errorMessage = $error['message'] ?? 'Sconosciuto';
                    return [
                        'status' => 'ERROR',
                        'id_product' => $id_product,
                        'url' => $image_url,
                        'error' => $errorCode,
                        'error_message' => $errorMessage,
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'httpCode' => 500,
                        'contentType' => 'text/plain',
                        'message' => "Immagine non salvata: file_puts_content failed, {$errorMessage}",
                    ];
                }

                // Genero le diverse dimensioni dell'immagine
                $image_types = ImageType::getImagesTypes('products');

                // Genero i thumbnails
                foreach ($image_types as $image_type) {
                    $destName = "{$image_path}-{$image_type['name']}.jpg";
                    $resized = ImageManager::resize(
                        $destOriginalImage,
                        $destName,
                        $image_type['width'],
                        $image_type['height']
                    );

                    if (!$resized) {
                        \PrestaShopLogger::addLog("Immagine non salvata: ImageManager::resize failed format {$image_type['name']}");
                    }
                }

                return [
                    'status' => 'OK',
                    'id_product' => $id_product,
                    'url' => $image_url,
                    'error' => 0,
                    'error_message' => '',
                    'file' => '',
                    'line' => '',
                    'httpCode' => 200,
                    'contentType' => 'text/plain',
                    'message' => 'Immagine salvata',
                ];
            }

            Db::getInstance()->displayError(true);
            $error = error_get_last();
            $errorCode = $error['type'] ?? 0;
            $errorMessage = $error['message'] ?? 'Sconosciuto';
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $errorCode,
                'error_message' => $errorMessage,
                'file' => $error['file'],
                'line' => $error['line'],
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => 'Immagine non salvata: ' . $errorMessage,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => 'Immagine non salvata: ' . $e->getMessage(),
            ];
        }
    }

    public static function guzzleDownload($url, $base64 = true)
    {
        $client = new \GuzzleHttp\Client();
        // Send a GET request to download the image
        try {
            $response = $client->get($url, [
                'http_errors' => false,
                'timeout' => 20,
                'connect_timeout' => 10,
                'verify' => false,
            ]);
        } catch (\Throwable $th) {
            return false;
        }

        if ($response->getStatusCode() >= 500) {
            return false;
        }

        // Get the content of the downloaded image
        $imageContent = $response->getBody()->getContents();

        return $base64 ? base64_encode($imageContent) : $imageContent;
    }

    public static function hasCover($id_product)
    {
        $db = Db::getInstance();
        $query = new DbQuery();

        $query
            ->select('id_image')
            ->from('image')
            ->where('id_product=' . (int) $id_product)
            ->where('cover=1');

        return (int) $db->getValue($query);
    }

    public static function getDeliveryDateDelay($delivery_date, $delay = 0)
    {
        if (!$delay) {
            return date('Y-m-d', strtotime($delivery_date));
        }

        $date = date('Y-m-d', strtotime("{$delivery_date} +{$delay} days"));

        return $date;
    }

    public static function updateDeliveryDate($id_product, $delivery_date)
    {
        $db = Db::getInstance();
        return (int) $db->update(
            'product_lang',
            [
                'delivery_in_stock' => $delivery_date,
            ],
            'id_product=' . (int) $id_product
        );
    }

    /**
     * Crea un array multilingua per i campi di un prodotto
     *
     * @param string $value Valore da inserire per tutte le lingue
     * @return array Array multilingua
     */
    protected static function createMultiLangField($value)
    {
        $result = [];
        foreach (Language::getLanguages() as $language) {
            if (is_array($value)) {
                $result[$language['id_lang']] = $value[$language['id_lang']] ?? reset($value);
            } else {
                $result[$language['id_lang']] = $value;
            }
        }
        return $result;
    }

    /**
     * Crea una descrizione HTML multilingua per il prodotto
     * @param array $product Dati del prodotto
     * @return array Array multilingua con la descrizione
     */
    protected static function createDescription($product)
    {
        $profile = $product['profile'] ?: '--';
        $name = $product['articlename'] ?: '--';
        $description = $product['description'] ?: '--';
        $manufacturerNumber = $product['manufacturer_number'] ?: '--';
        $vehicleClass = $product['vehicle_class'] ?: '--';
        $season = $product['usage'] ?: '--';
        $measures = "{$product['width']}/{$product['height']}/{$product['tyre_type']}{$product['inner_diameter']}";
        $measure = str_replace('/', '', $measures);
        $brandname = $product['brandname'] ?: '--';

        $description = "
            <h2>Informazioni prodotto</h2>
            <h3>Classe veicolo: {$vehicleClass}</h3>
            <p>MARCA: {$brandname}</p>
            <p>Pneumatico: {$name}</p>
            <p>Descrizione: {$description}</p>
            <p>Profilo: {$profile}</p>
            <p>Numero articolo: {$manufacturerNumber}</p>
            <p>USO: {$season}</p>
            <p>Dimensioni: {$measures}</p>
            <p>GRANDEZZA: {$measure}</p>
        ";

        return self::createMultiLangField($description);
    }

    /**
     * Cerca o crea il produttore
     *
     * @param array $manufacturerArray Dati del produttore
     * @return int ID del valore del produttore
     */
    protected static function getOrCreateManufacturer($manufacturerArray)
    {
        $languages = Language::getLanguages();
        $id_manufacturer = (int) Manufacturer::getIdByName($manufacturerArray['name']);

        // Se esiste già, restituisco l'ID
        if ($id_manufacturer > 0) {
            return $id_manufacturer;
        }

        $manufacturer = new Manufacturer();

        $manufacturer->name = strtoupper($manufacturerArray['name']);
        $manufacturer->active = 1;

        foreach ($languages as $language) {
            $manufacturer->description[$language['id_lang']] = $manufacturerArray['description'];
        }

        $result = $manufacturer->save();

        if ($result) {
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

            try {
                if (@file_put_contents($temp_name, file_get_contents($image_url))) {
                    $image_downloaded = true;
                }
            } catch (\Throwable $th) {
                echo "\n\t Errore {$th->getMessage()} durante il salvataggio dell'immagine: {$image_url}";
                $image_downloaded = false;
            }

            if ($image_downloaded) {
                // Genero il nome dell'immagine
                $image_name = $id_manufacturer . '.jpg';

                // Salvo l'immagine nella directory dei manufacturer
                if (ImageManager::resize($temp_name, _PS_MANU_IMG_DIR_ . $image_name)) {
                    // Genero i thumbnails
                    $images_types = ImageType::getImagesTypes('manufacturers');
                    foreach ($images_types as $image_type) {
                        ImageManager::resize(
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

            return $id_manufacturer;
        }

        return false;
    }

    /**
     * Cerca o crea una caratteristica
     *
     * @param string $name Nome della caratteristica
     * @return int ID della caratteristica
     */
    protected static function getOrCreateFeature($name)
    {
        $default_lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        // Cerco la caratteristica per nome
        $id_feature = Db::getInstance()->getValue(
            'SELECT f.id_feature
            FROM `' . _DB_PREFIX_ . 'feature` f
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
            ON (f.id_feature = fl.id_feature AND fl.id_lang = ' . (int) $default_lang_id . ')
            WHERE fl.name = "' . pSQL($name) . '"'
        );

        if (!$id_feature) {
            // Creo la caratteristica
            $feature = new \Feature();
            $feature->name = self::createMultiLangField($name);
            $feature->add();
            $id_feature = $feature->id;
        }

        return $id_feature;
    }

    /**
     * Cerca o crea un valore per una caratteristica
     *
     * @param int $id_feature ID della caratteristica
     * @param string $value Valore della caratteristica
     * @return int ID del valore della caratteristica
     */
    protected static function getOrCreateFeatureValue($id_feature, $value)
    {
        $default_lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $id_feature_value = Db::getInstance()->getValue(
            'SELECT fv.id_feature_value
            FROM `' . _DB_PREFIX_ . 'feature_value` fv
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
            ON (fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int) $default_lang_id . ')
            WHERE fv.id_feature = ' . (int) $id_feature . ' AND fvl.value = "' . pSQL($value) . '"'
        );

        if (!$id_feature_value) {
            // Creo il valore della caratteristica
            $feature_value = new \FeatureValue();
            $feature_value->id_feature = $id_feature;
            $feature_value->value = self::createMultiLangField($value);
            $feature_value->add();
            $id_feature_value = $feature_value->id;
        }

        return $id_feature_value;
    }

    public static function countProducts()
    {
        $db = Db::getInstance();
        return (int) $db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_csv`');
    }

    public static function countProductsFiltered()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('product_csv');
        $where = self::getWhereFilter();

        $query->where($where);

        $query = $query->build();

        return (int) $db->getValue($query);
    }

    public static function getWhereFilter()
    {
        $where = [];
        $filter_ms = (int) Configuration::get('MPAPITYRES_FILTER_MS');
        $filter_ms_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_MS_MANDATORY');
        if ($filter_ms) {
            if ($filter_ms_mandatory) {
                $where[] = ' AND ms=1';
            } else {
                $where[] = ' OR ms=1';
            }
        }
        $filter_runflat = (int) Configuration::get('MPAPITYRES_FILTER_RUNFLAT');
        $filter_runflat_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_RUNFLAT_MANDATORY');
        if ($filter_runflat) {
            if ($filter_runflat_mandatory) {
                $where[] = ' AND runflat=1';
            } else {
                $where[] = ' OR runflat=1';
            }
        }
        $filter_da_decke = (int) Configuration::get('MPAPITYRES_FILTER_DA_DECKE');
        $filter_da_decke_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DA_DECKE_MANDATORY');
        if ($filter_da_decke) {
            if ($filter_da_decke_mandatory) {
                $where[] = ' AND da_decke=1';
            } else {
                $where[] = ' OR da_decke=1';
            }
        }
        $filter_demo = (int) Configuration::get('MPAPITYRES_FILTER_DEMO');
        $filter_demo_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DEMO_MANDATORY');
        if ($filter_demo) {
            if ($filter_demo_mandatory) {
                $where[] = ' AND demo=1';
            } else {
                $where[] = ' OR demo=1';
            }
        }
        $filter_dot = (int) Configuration::get('MPAPITYRES_FILTER_DOT');
        $filter_dot_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DOT_MANDATORY');
        if ($filter_dot) {
            if ($filter_dot_mandatory) {
                $where[] = ' AND dot=1';
            } else {
                $where[] = ' OR dot=1';
            }
        }
        $filter_3pmfs = (int) Configuration::get('MPAPITYRES_FILTER_3PMFS');
        $filter_3pmfs_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_3PMFS_MANDATORY');
        if ($filter_3pmfs) {
            if ($filter_3pmfs_mandatory) {
                $where[] = ' AND `three_pmfs`=1';
            } else {
                $where[] = ' OR `three_pmfs`=1';
            }
        }
        $filter_quantity_min = (int) Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN');
        $filter_quantity_min_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN_MANDATORY');
        if ($filter_quantity_min) {
            if ($filter_quantity_min_mandatory) {
                $where[] = ' AND availability>=' . (int) $filter_quantity_min;
            } else {
                $where[] = ' OR availability>=' . (int) $filter_quantity_min;
            }
        }
        $where = implode(' ', $where);
        // Elimino la condizione OR o AND dall'inizio della stringa
        $where = ltrim($where, ' OR ');
        $where = ltrim($where, ' AND ');
        $where = trim($where);

        return "1 AND ({$where})";
    }

    public static function cleanUp()
    {
        $start = microtime(true);
        $db = Db::getInstance();
        $pfx = _DB_PREFIX_;
        $where = self::getWhereFilter();
        $subquery = "
            SELECT id_product FROM {$pfx}product_csv WHERE {$where}
        ";

        $query = "
            SELECT id_product FROM {$pfx}product
            WHERE
                id_product NOT IN ({$subquery})
                AND reference NOT LIKE 'PFU-%'
        ";

        $list = $db->executeS($query);
        $count = count($list);

        echo "Trovati {$count} prodotti da disattivare\n";

        foreach ($list as $item) {
            $product = new Product($item['id_product']);
            if ($product->id) {
                $product->active = 0;
                $product->update();
            }
            StockAvailable::setQuantity($item['id_product'], 0, 0);
        }

        $end = self::humanReadableSeconds(microtime(true) - $start);
        echo "Operazione completata in {$end}\n";
    }

    public static function addPFU()
    {
        $pfx = _DB_PREFIX_;
        $db = Db::getInstance();

        $query = "
            SELECT
                id_product
            FROM {$pfx}product
            WHERE
                active = 1
                AND 
                reference NOT LIKE 'PFU-%'
                AND id_product NOT IN 
                    (SELECT distinct id_product FROM {$pfx}product_pfu)
            ORDER BY id_product ASC
        ";

        $result = $db->executeS($query);
        $pfu = new CreatePFU();

        foreach ($result as $row) {
            $id_product = $row['id_product'];
            $pfu->setProductToPfu($id_product);
            echo "\n\t{$id_product}: PFU creato.";
        }

        echo "\n*** OPERAZIONE ESEGUITA ***\n";
    }

    public static function recalcWeight()
    {
        $db = Db::getInstance();
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                id_product,
                width,
                height,
                depth
            FROM {$pfx}product
            WHERE
                reference NOT LIKE 'PFU-%'
            ORDER BY id_product ASC
        ";

        $result = $db->executeS($query);
        $pfu = new CreatePFU();

        foreach ($result as $row) {
            $id_product = $row['id_product'];
            $width = (int) $row['width'];
            $height = (int) $row['height'];
            $depth = (int) $row['depth'];
            $tyre = "{$width}/{$height}/{$depth}";
            $tyreWeight = TyreWeight::calcByCode($tyre);
            if ($tyreWeight == 0) {
                echo "\n\t{$id_product}: peso non valido. Prodotto disattivato.";
                $product = new Product($id_product);
                if ($product->id) {
                    $product->active = 0;
                    $product->update();
                }
                StockAvailable::setQuantity($id_product, 0, 0);
            } else {
                $product = new Product($id_product);
                $product->weight = $tyreWeight;
                $product->update();
                echo "\n\t{$id_product}: peso aggiornato a {$tyreWeight}Kg.";

                $pfu->setProductToPfu($id_product);
            }
        }

        echo "\n*** OPERAZIONE ESEGUITA ***\n";
    }
}
