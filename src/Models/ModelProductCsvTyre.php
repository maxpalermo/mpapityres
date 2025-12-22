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
use MpSoft\MpApiTyres\Helpers\PFU;
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

class ModelProductCsvTyre extends \ObjectModel
{
    /**
     * Chiave primaria numerica
     * (colonna `id` nella tabella, gestita da ObjectModel tramite la proprietà $id)
     */

    /**
     * @var string
     */
    public $matchcode;

    /**
     * @var float
     */
    public $outer_diameter;

    /**
     * @var float
     */
    public $height;

    /**
     * @var float
     */
    public $width;

    /**
     * @var string
     */
    public $tyre_type;

    /**
     * @var float
     */
    public $inner_diameter;

    /**
     * @var float
     */
    public $weight;

    /**
     * @var int
     */
    public $id_product_pfu;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $brandname;

    /**
     * @var string
     */
    public $profile;

    /**
     * @var string
     */
    public $articlename;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $manufacturer_number;

    /**
     * @var string
     */
    public $usage;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $load_index;

    /**
     * @var string
     */
    public $speed_index;

    /**
     * @var string
     */
    public $runflat;

    /**
     * @var string
     */
    public $ms;

    /**
     * @var string
     */
    public $three_pmfs;

    /**
     * @var string
     */
    public $da_decke;

    /**
     * @var string
     */
    public $price_1;

    /**
     * @var string
     */
    public $price_4;

    /**
     * @var string
     */
    public $avg_price;

    /**
     * @var string
     */
    public $price_anonym;

    /**
     * @var string
     */
    public $price_rvo;

    /**
     * @var string
     */
    public $availability;

    /**
     * @var string
     */
    public $expected_delivery_date;

    /**
     * @var string
     */
    public $direct_link;

    /**
     * @var string
     */
    public $info;

    /**
     * @var string
     */
    public $image;

    /**
     * @var string
     */
    public $image_tn;

    /**
     * @var string
     */
    public $tyrelabel_link;

    /**
     * @var string
     */
    public $energy_efficiency_index;

    /**
     * @var string
     */
    public $wet_grip_index;

    /**
     * @var string
     */
    public $noise_index;

    /**
     * @var string
     */
    public $noise_decible;

    /**
     * @var string
     */
    public $vehicle_class;

    /**
     * @var string
     */
    public $ice_grip;

    /**
     * @var string
     */
    public $data_sheet;

    /**
     * @var string
     */
    public $demo;

    /**
     * @var string
     */
    public $dot;

    /**
     * @var string
     */
    public $dot_year;

    /**
     * @var string
     */
    public $price_anonym_one;

    /**
     * @var string
     */
    public $manufacturer_description;

    /**
     * @var string
     */
    public $ean;

    /**
     * @var string
     */
    public $hash;

    /**
     * @var bool
     */
    public $active;

    /**
     * @var string
     */
    public $date_add;

    /**
     * @var string
     */
    public $date_upd;

    public const CSV_URL = 'https://tyre24.alzura.com//it/it/export/download-via-token/token/{token}/accountId/{accountId}/t/1/c/35/';

    public static $definition = [
        'table' => 'product_csv',
        'primary' => 'id',
        'fields' => [
            // Tutti i campi (tranne la PK `id`) sono trattati come stringhe
            'matchcode' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'outer_diameter' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFLoat'],
            'height' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'width' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'tyre_type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 1],
            'inner_diameter' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'id_product_pfu' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'class' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 16],
            'brandname' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'profile' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'articlename' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'description' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'manufacturer_number' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'usage' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 32],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 2],
            'load_index' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 16],
            'speed_index' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 16],
            'runflat' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'ms' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'three_pmfs' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'da_decke' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'price_1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'price_4' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'avg_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'price_anonym' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'price_rvo' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'availability' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'expected_delivery_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'direct_link' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'info' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'image' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255],
            'image_tn' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'tyrelabel_link' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'energy_efficiency_index' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'wet_grip_index' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 1],
            'noise_index' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'noise_decible' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'vehicle_class' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 2],
            'ice_grip' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'data_sheet' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything'],
            'demo' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'dot' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'dot_year' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'price_anonym_one' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'manufacturer_description' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 1024],
            'ean' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 13],
            'hash' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 32],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'default' => 1],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $context = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $context);
    }

    public static function checkTableExists()
    {
        $pfx = _DB_PREFIX_;
        $db_name = _DB_NAME_;

        $QUERY = "
            SELECT COUNT(*) AS table_exists
            FROM information_schema.tables
            WHERE table_schema = '{$db_name}'
            AND table_name = '{$pfx}product_csv';
        ";

        return (int) Db::getInstance()->getValue($QUERY);
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS {$pfx}product_csv (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `matchcode` VARCHAR(255) NOT NULL,
            `outer_diameter` DECIMAL(6,2) UNSIGNED NOT NULL,
            `height` DECIMAL(6,2) UNSIGNED NOT NULL,
            `width` DECIMAL(6,2) NOT NULL,
            `tyre_type` CHAR(1) NULL,
            `inner_diameter` DECIMAL(6,2) UNSIGNED NOT NULL,
            `weight` DECIMAL(6,2) UNSIGNED NOT NULL DEFAULT 0,
            `id_product_pfu` INT(11) NOT NULL DEFAULT 0,
            `class` VARCHAR(16) DEFAULT 'unknown',
            `brandname` VARCHAR(255) NULL,
            `profile` VARCHAR(255) NULL,
            `articlename` VARCHAR(255) NOT NULL,
            `description` VARCHAR(255) NULL,
            `manufacturer_number` VARCHAR(255) NULL,
            `usage` VARCHAR(32) NULL,
            `type` VARCHAR(2) NULL,
            `load_index` VARCHAR(16) NULL,
            `speed_index` VARCHAR(16) NULL,
            `runflat` TINYINT(1) NOT NULL DEFAULT 0,
            `ms` TINYINT(1) NOT NULL DEFAULT 0,
            `three_pmfs` TINYINT(1) NOT NULL DEFAULT 0,
            `da_decke` TINYINT(1) NOT NULL DEFAULT 0,
            `price_1` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `price_4` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `avg_price` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `price_anonym` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            `price_rvo` VARCHAR(255) NULL,
            `availability` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `expected_delivery_date` DATE NULL,
            `direct_link` VARCHAR(255) NULL,
            `info` VARCHAR(255) NULL,
            `image` VARCHAR(255) NULL,
            `image_tn` VARCHAR(255) DEFAULT NULL,
            `tyrelabel_link` VARCHAR(255) DEFAULT NULL,
            `energy_efficiency_index` VARCHAR(255) DEFAULT NULL,
            `wet_grip_index` CHAR(1) DEFAULT NULL,
            `noise_index` INT(11) UNSIGNED DEFAULT NULL,
            `noise_decible` INT(11) UNSIGNED DEFAULT NULL,
            `vehicle_class` CHAR(2) DEFAULT NULL,
            `ice_grip` TINYINT(1) DEFAULT NULL,
            `data_sheet` VARCHAR(255) DEFAULT NULL,
            `demo` TINYINT(1) DEFAULT NULL,
            `dot` TINYINT(1) DEFAULT NULL,
            `dot_year` INT(11) UNSIGNED DEFAULT NULL,
            `price_anonym_one` DECIMAL(20,6) DEFAULT 0.000000,
            `manufacturer_description` TEXT DEFAULT NULL,
            `ean` VARCHAR(13) DEFAULT NULL,
            `hash` CHAR(32) DEFAULT NULL,
            `active` BOOL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE={$engine};
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

    public static function insertData($csvPath, $delimiter = '|', $hash)
    {
        $errors = [];
        $inserted = 0;
        $skipExists = (int) Configuration::get('MPAPITYRES_SKIP_EXISTS');
        $skipped = 0;
        $updated = 0;
        $start = microtime(true);
        $weight = 0;
        $class = '';

        foreach (self::readCsvStream($csvPath, $delimiter) as $row) {
            /*
             * Cambio la dimensione dell'immagine del prodotto da -w[d+]-h[d+] a -w300-h300
             */
            $image = $row['image'];
            if (strpos($image, '-w') !== false) {
                $image = preg_replace(['/-w\d+/', '/-h\d+/'], ['-w300', '-h300'], $image);
            } else {
                $image = '404.gif';
            }

            $row['image'] = $image;

            /*
             * controllo se tyrelabel_link è valido
             * valido: https://media5.tyre-shopping.com/tyrelabel2/C/B/A/67/1/0/0/31bf14ee3c6248f1a24bf1fbadfef348-h200-w200.jpg?manufacturer=LINGLONG&model=&size=145%2F65R1572T&qr=aHR0cHM6Ly9lcHJlbC5lYy5ldXJvcGEuZXUvcXIvNTU4MDMy
             * non valido: https://media1.tyre-shopping.com/tyrelabel2////0/0/0/0/93c2ed3825d921523dbf31e12fc2760e-h200-w200.jpg?manufacturer=&model=&size=&qr=
             * se è valido prendo solo la parte fino a ? e cambio le dimensioni -h200-w200 in -h800-w800
             */
            $tyreLabel = $row['tyrelabel_link'];
            $tyreLabel = static::removeUrlQueryParams($tyreLabel, ['manufacturer', 'model', 'size', 'qr']);

            // prendo la prima parte della stringa fino a ?
            if (strpos($tyreLabel, '?') !== false) {
                $tyreLabel = substr($tyreLabel, 0, strpos($tyreLabel, '?'));
            }
            // cambio le dimensioni -h[d+]-w[d+] in -h300-w300
            $tyreLabel = preg_replace(['/-h\d+/', '/-w\d+/'], ['-h300', '-w300'], $tyreLabel);

            $row['tyrelabel_link'] = $tyreLabel;

            /*
             * controllo se il campo type è compreso in 2 caratteri
             */
            if (strlen($row['type']) > 2) {
                $errors[] = "{$row['id']}: Il campo type deve essere compreso in 2 caratteri\t ==> {$row['type']}\n";
                $row['type'] = '';
            }

            /*
             * controllo se il campo tyre_type è compreso in 2 caratteri
             */
            if (strlen($row['tyre_type']) > 2) {
                $errors[] = "{$row['id']}: Il campo tyre_type deve essere compreso in 2 caratteri\t ==> {$row['tyre_type']}\n";
                $row['tyre_type'] = '';
            }

            /*
             * controllo se il campo article_name è compilato
             */
            if (empty(trim($row['articlename']))) {
                $errors[] = "{$row['id']}: Il campo articlename è obbligatorio\n";
                continue;
            }

            /** Controllo se il campo expected-delivery-date è valido, se no salto l'inserimento */
            if (!Validate::isDate($row['expected_delivery_date'])) {
                echo "Data di consegna non valida per il prodotto {$row['id']}\n";
                continue;
            }

            // Calcolo del peso
            // $weight = PFU::getWeightKg((float) $row['width'], (float) $row['aspect_ratio'], (float) $row['outer_diameter'], (float) $row['inner_diameter']);
            $weight = PFU::estimateTyreWeightKg((float) $row['width'], (float) $row['height'], (float) $row['inner_diameter']);
            $id_product_pfu = PFU::getIdPfu($weight);

            $product = new ModelProductCsvTyre($row['id']);

            // Se esiste è il flag skipExists è attivo salto il prodotto
            if (Validate::isLoadedObject($product) && $skipExists) {
                echo "\n{$row['id']} esiste già. Saltato.\n";
                $skipped++;
                continue;
            }

            $product->matchcode = $row['matchcode'];
            $product->outer_diameter = (float) $row['outer_diameter'];
            $product->height = (float) $row['height'];
            $product->width = (float) $row['width'];
            $product->tyre_type = $row['tyre_type'];
            $product->inner_diameter = (float) ($row['inner_diameter'] ?: $row['outer_diameter']);
            $product->weight = (float) $weight;
            $product->class = $class;
            $product->id_product_pfu = (int) $id_product_pfu;
            $product->brandname = $row['brandname'];
            $product->profile = $row['profile'];
            $product->articlename = $row['articlename'];
            $product->description = $row['description'];
            $product->manufacturer_number = $row['manufacturer_number'];
            $product->usage = $row['usage'];
            $product->type = $row['type'];
            $product->load_index = $row['load_index'];
            $product->speed_index = $row['speed_index'];
            $product->runflat = (int) $row['runflat'];
            $product->ms = (int) $row['ms'];
            $product->three_pmfs = (int) $row['3pmfs'];
            $product->da_decke = (int) $row['da_decke'];
            $product->price_1 = (float) $row['price_1'];
            $product->price_4 = (float) $row['price_4'];
            $product->avg_price = (float) $row['avg_price'];
            $product->price_anonym = (float) $row['price_anonym'];
            $product->price_rvo = $row['price_rvo'];
            $product->availability = (int) $row['availability'];
            $product->expected_delivery_date = $row['expected_delivery_date'];
            $product->direct_link = $row['direct_link'];
            $product->info = $row['info'];
            $product->image = $row['image'];
            $product->image_tn = $row['image_tn'];
            $product->tyrelabel_link = $row['tyrelabel_link'];
            $product->energy_efficiency_index = $row['energy_efficiency_index'];
            $product->wet_grip_index = $row['wet_grip_index'];
            $product->noise_index = (int) $row['noise_index'];
            $product->noise_decible = (int) $row['noise_decible'];
            $product->vehicle_class = $row['vehicle_class'];
            $product->ice_grip = (int) $row['ice_grip'];
            $product->data_sheet = $row['data_sheet'];
            $product->demo = (int) $row['demo'];
            $product->dot = $row['dot'];
            $product->dot_year = (int) $row['dot_year'];
            $product->price_anonym_one = (float) $row['price_anonym_one'];
            $product->manufacturer_description = $row['manufacturer_description'];
            $product->ean = $row['ean'] ?? null;
            $product->hash = $hash;
            $product->active = 1;
            $product->date_add = date('Y-m-d H:i:s');
            $product->date_upd = null;

            try {
                if (Validate::isLoadedObject($product)) {
                    $product->date_upd = date('Y-m-d H:i:s');
                    $product->update(true);
                    $updated++;
                    echo ":{$row['id']}:,";
                } else {
                    $product->force_id = true;
                    $product->id = $row['id'];
                    $product->add(false, true);
                    echo "+{$row['id']}+,";
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

    protected static function removeUrlQueryParams($url, array $paramsToRemove)
    {
        if (!$url) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        foreach ($paramsToRemove as $key) {
            unset($query[$key]);
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $auth = $user ? $user . $pass . '@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        $queryString = http_build_query($query);
        $queryString = $queryString ? '?' . $queryString : '';

        return $scheme . $auth . $host . $port . $path . $queryString . $fragment;
    }

    public static function disableNotHash($hash)
    {
        $pfx = _DB_PREFIX_;
        $sql = "
            UPDATE
                {$pfx}product p
            INNER JOIN
                {$pfx}product_shop ps
                ON (p.id_product=ps.id_product)
            INNER JOIN
                {$pfx}product_tyre tyre
                ON (p.id_product=tyre.id_product AND tyre.type_tyre='tyre')
            INNER JOIN
                {$pfx}product_csv csv
                ON (tyre.id_tyre=csv.id)
            SET
                p.active=0,
                ps.active=0
            WHERE
                csv.hash != '{$hash}'
        ";

        return Db::getInstance()->execute($sql);
    }

    public static function disableByHash($hash)
    {
        $pfx = _DB_PREFIX_;
        $table = $pfx . 'product_csv';
        $hash = pSQL($hash);
        $query = "UPDATE {$table} SET active = 0 WHERE `hash` <> '{$hash}'";
        Db::getInstance()->execute($query);

        return Db::getInstance()->Affected_Rows();
    }

    public static function downloadCsv($url)
    {
        $start = microtime(true);

        // Scarico il file ZIP tramite guzzleHTTP
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
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
        $count = count($rows);
        echo "\nTrovate {$count} righe nella tabella CSV su {$totalRows} totali\n";

        foreach ($rows as $row) {
            self::updateProduct($row);
        }

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
            ->where('active = 1')
            ->orderBy(self::$definition['primary'] . ' ASC');

        echo "\n=================================================\n";
        echo "\nQUERY DI RICERCA: " . $query->build();
        echo "\n=================================================\n\n\n";

        return $db->executeS($query);
    }

    public static function disableProducts()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $where = self::getWhereFilter();
        $query
            ->select('id')
            ->from(self::$definition['table'])
            ->where($where)
            ->where('active = 1')
            ->orderBy(self::$definition['primary'] . ' ASC');

        echo "\n=================================================\n";
        echo "\nQUERY DISATTIVAZIONE PRODOTTI: " . $query->build();
        echo "\n=================================================\n\n\n";

        $list = $db->executeS($query);
        $ids = implode(',', array_map(function ($row) {
            return $row['id'];
        }, $list));

        $pfx = _DB_PREFIX_;
        $queryDisable = "
            SELECT
                p.id_product
            FROM
                {$pfx}product p
            INNER JOIN
                {$pfx}product_tyre t
                ON (t.id_product = p.id_product and t.type_tyre='tyre')
            WHERE
                t.id_tyre NOT IN ({$ids})
                AND p.active = 1
        ";

        $resultDisable = $db->executeS($queryDisable);

        echo PHP_EOL;

        if (is_array($resultDisable) && count($resultDisable)) {
            $idsToDisable = array_map(function ($row) {
                return $row['id_product'];
            }, $resultDisable);
            foreach ($idsToDisable as $id) {
                $product = new Product($id);
                $product->active = 0;
                $product->update();
                echo '.';
            }
        } else {
            $resultDisable = [];
        }

        $queryPfu = "
            SELECT
                p.id_product
            FROM
                {$pfx}product p
            INNER JOIN
                {$pfx}product_tyre t
                ON (t.id_product = p.id_product and t.type_tyre='pfu')
        ";

        echo PHP_EOL;
        $resultPfu = $db->executeS($queryPfu);
        if (is_array($resultPfu) && count($resultPfu)) {
            $idsToDisable = array_map(function ($row) {
                return $row['id_product'];
            }, $resultPfu);
            foreach ($idsToDisable as $id) {
                $product = new Product($id);
                $product->active = 1;
                $product->update();
                echo '.';
            }
        } else {
            $resultPfu = [];
        }

        echo "\n=================================================\n";
        echo '\PRODOTTI DISABILITATI: ' . count($resultDisable);
        echo "\n=================================================\n\n\n";
    }

    protected static function getProductByIdTyre($id_tyre)
    {
        $pfx = _DB_PREFIX_;
        $QUERY = "
            SELECT
                p.id_product
            FROM
                {$pfx}product p
            INNER JOIN
                {$pfx}product_csv csv 
                ON (p.reference = csv.matchcode)
            WHERE
                csv.id = {$id_tyre}
        ";

        $db = Db::getInstance();
        $id = (int) $db->getValue($QUERY);

        return new Product($id);
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
        $skipOnBadWeight = (bool) Configuration::get('MPAPITYRES_SKIP_ON_BAD_WEIGHT');
        $id_tax_rules_group = (int) Configuration::get('MPAPITYRES_TAX_RULES_GROUP_ID');
        $id_default_category = (int) Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED');
        $load_amount = (float) Configuration::get('MPAPITYRES_LOAD_AMOUNT');
        $load_percentage = (float) Configuration::get('MPAPITYRES_LOAD_PERCENTAGE');

        $id_product = 0;
        $id_product_pfu = (int) $product['id_product_pfu'];
        $id_tyre = (int) ($product['id'] ?? 0);
        $match_code = trim(($product['matchcode'] ?? ''));
        $article_name = trim(($product['articlename'] ?? ''));
        $delivery_date = trim(($product['expected_delivery_date'] ?? ''));
        $manufacturer_name = $product['brandname'];
        $reference = $product['matchcode'];

        $price_1_loaded = (float) self::calcLoadPriceByPriceRange($product['price_1']);
        $price_4_loaded = (float) self::calcLoadPriceByPriceRange($product['price_4']);
        $delivery_delay_days = (int) Configuration::get('MPAPITYRES_DELIVERY_DELAY_DAYS');
        $weight = (float) $product['weight'];

        if (!$id_tyre) {
            echo "\n\t Non ho il codice prodotto. Importazione saltata.";
            return false;
        }

        if (!$match_code) {
            echo "\n\t Non ho il matchcode per il prodotto {$id_tyre}. Importazione saltata.";
            return false;
        }

        if (!$article_name) {
            echo "\n\t Non ho il nome per il prodotto {$id_tyre}. Importazione saltata.";
            return false;
        }

        if (!Validate::isDateFormat($delivery_date)) {
            echo "\n\t Data di consegna non valida per il prodotto {$id_tyre}: {$delivery_date}";
            return false;
        }

        // Aggiorno la scadenza
        $deliveryDate = $delivery_date;
        $delayDate = self::getDeliveryDateDelay($deliveryDate, $delivery_delay_days);

        // trovo o creo il produtore
        $id_manufacturer = ModelProductTyre::getOrCreateManufacturer($manufacturer_name, $id_tyre);

        // id_product è creato automaticamente
        // id_tyre è messo nella tabella product_tyre
        $db = Db::getInstance();
        $id_product = 0;

        try {
            $productObj = static::getProductByIdTyre($id_tyre);
            // $productObj = new Product($id_product, false);

            if (Validate::isLoadedObject($productObj)) {
                $id_product = (int) $productObj->id;

                if ($product['price_1'] == 0) {
                    $productObj->active = 0;
                    $productObj->price = 0;
                    $productObj->save();

                    self::removeQuantityPrice($id_product, 4);

                    echo "\n\t Prodotto {$id_product} disattivato e prezzo impostato a 0";
                    return true;
                }

                $productObj->name = self::createMultiLangField($article_name);
                $productObj->reference = $match_code;
                $productObj->description_short = self::createDescription($product);
                $productObj->link_rewrite = self::createMultiLangField(
                    \Tools::str2url($article_name ?? 'product-' . $reference)
                );
                try {
                    $productObj->update();
                } catch (\Throwable $th) {
                    echo "\n\t Errore durante l'update del prodotto {$id_product}: {$th->getMessage()}";
                    return false;
                }

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
                PFU::setPfuToProduct($id_product, $id_product_pfu, $id_tyre, $weight, $price_1_loaded);

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
            $prestashopProduct = static::getProductByIdTyre($id_tyre);

            // Dati base del prodotto
            $prestashopProduct->reference = $reference;
            $prestashopProduct->supplier_reference = $id_tyre;
            $prestashopProduct->name = self::createMultiLangField($article_name);
            $prestashopProduct->description_short = self::createDescription($product);
            $prestashopProduct->link_rewrite = self::createMultiLangField(
                \Tools::str2url($article_name ?? 'product-' . $reference)
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
            $prestashopProduct->available_date = date('Y-m-d H:i:s');
            $prestashopProduct->product_type = 'standard';
            $prestashopProduct->state = 1;
            $prestashopProduct->advanced_stock_management = 0;
            $prestashopProduct->pack_stock_type = 3;

            // Categorie
            $prestashopProduct->id_category_default = $id_default_category;
            // Produttore
            $prestashopProduct->id_manufacturer = $id_manufacturer;
            // Dimensioni e peso
            $prestashopProduct->width = (float) ($product['width'] ?? 0);
            $prestashopProduct->height = (float) ($product['height'] ?? 0);
            $prestashopProduct->depth = (float) ($product['inner_diameter'] ?? 0);
            $prestashopProduct->weight = (float) ($product['weight'] ?? 0);

            if ($skipOnBadWeight) {
                if ($prestashopProduct->weight == 0 || $prestashopProduct->width == 0 || $prestashopProduct->height == 0 || $prestashopProduct->depth == 0) {
                    echo "\n\t Pneumatico {$reference}: Peso non valido. Importazione saltata.";
                    return false;
                }
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

            // Creo il record nella tabella product_tyre
            ModelProductTyre::addProduct($id_product, $id_tyre, 'tyre', 0, 0, $price_1_loaded, 0);

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
            PFU::setPfuToProduct($id_product, $id_product_pfu, $id_tyre, $weight, $price_1_loaded);

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

    public static function calcLoadPriceByPriceRange($price)
    {
        $db = Db::getInstance();
        $pfx = _DB_PREFIX_;
        $QUERY = "
            SELECT
                reload_amount,
                reload_perc
            FROM {$pfx}product_price_reload
            WHERE price_min <= {$price}
                AND {$price} < price_max
            ORDER BY price_min DESC
        ";

        $result = $db->getRow($QUERY);

        if ($result) {
            $price = self::calcLoadPrice($price, $result['reload_amount'], $result['reload_perc']);
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
        if (!(int) $id_product) {
            return false;
        }

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
                } else {
                    chmod($destOriginalImage, 0775);
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

        $width = (float) $product['width'];
        $height = (float) $product['height'];
        $innerDiameter = (float) ($product['inner_diameter'] ?: $product['outer_diameter']);
        $r = $product['tyre_type'] ?: '';

        $measures = "{$width}/{$height}/{$r}{$innerDiameter}";
        $measure = str_replace('/', '', $measures);
        $brandname = $product['brandname'] ?: '--';
        $dataSheet = $product['data_sheet'] ?: '';

        if ($dataSheet) {
            $dataSheet = "<p>Documento: <a href='{$dataSheet}' target='_blank'><span class='btn btn-sm'>Visualizza</span></a></p>";
        }

        $description = "
            <h2>Informazioni prodotto</h2>
            <h3>Classe veicolo: {$vehicleClass}</h3>
            <br>
            <p>Marca: <strong>{$brandname}</strong></p>
            <p>Riferimento produttore: <strong>{$product['id']}</strong></p> 
            <p>Pneumatico: <strong>{$name}</strong></p>
            <p>Descrizione: <strong>{$description}</strong></p>
            <p>Profilo: <strong>{$profile}</strong></p>
            <p>Numero articolo: <strong>{$manufacturerNumber}</strong></p>
            <p>USO: <strong>{$season}</strong></p>
            <p>Dimensioni: <strong>{$measures}</strong></p>
            <p>GRANDEZZA: <strong>{$measure}</strong></p>
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
        $whereAnd = [];
        $whereOr = [];
        $filter_ms = (int) Configuration::get('MPAPITYRES_FILTER_MS');
        $filter_ms_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_MS_MANDATORY');
        if ($filter_ms) {
            if ($filter_ms_mandatory) {
                $whereAnd[] = ' AND ms=1';
            } else {
                $whereOr[] = ' OR ms=1';
            }
        }
        $filter_runflat = (int) Configuration::get('MPAPITYRES_FILTER_RUNFLAT');
        $filter_runflat_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_RUNFLAT_MANDATORY');
        if ($filter_runflat) {
            if ($filter_runflat_mandatory) {
                $whereAnd[] = ' AND runflat=1';
            } else {
                $whereOr[] = ' OR runflat=1';
            }
        }
        $filter_da_decke = (int) Configuration::get('MPAPITYRES_FILTER_DA_DECKE');
        $filter_da_decke_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DA_DECKE_MANDATORY');
        if ($filter_da_decke) {
            if ($filter_da_decke_mandatory) {
                $whereAnd[] = ' AND da_decke=1';
            } else {
                $whereOr[] = ' OR da_decke=1';
            }
        }
        $filter_demo = (int) Configuration::get('MPAPITYRES_FILTER_DEMO');
        $filter_demo_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DEMO_MANDATORY');
        if ($filter_demo) {
            if ($filter_demo_mandatory) {
                $whereAnd[] = ' AND demo=1';
            } else {
                $whereOr[] = ' OR demo=1';
            }
        }
        $filter_dot = (int) Configuration::get('MPAPITYRES_FILTER_DOT');
        $filter_dot_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_DOT_MANDATORY');
        if ($filter_dot) {
            if ($filter_dot_mandatory) {
                $whereAnd[] = ' AND dot=1';
            } else {
                $whereOr[] = ' OR dot=1';
            }
        }
        $filter_3pmfs = (int) Configuration::get('MPAPITYRES_FILTER_3PMFS');
        $filter_3pmfs_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_3PMFS_MANDATORY');
        if ($filter_3pmfs) {
            if ($filter_3pmfs_mandatory) {
                $whereAnd[] = ' AND `three_pmfs`=1';
            } else {
                $whereOr[] = ' OR `three_pmfs`=1';
            }
        }
        $filter_quantity_min = (int) Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN');
        $filter_quantity_min_mandatory = (int) Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN_MANDATORY');
        if ($filter_quantity_min) {
            if ($filter_quantity_min_mandatory) {
                $whereAnd[] = ' AND availability>=' . (int) $filter_quantity_min;
            } else {
                $whereOr[] = ' OR availability>=' . (int) $filter_quantity_min;
            }
        }
        $whereAnd = implode(' ', $whereAnd);
        $whereOr = implode(' ', $whereOr);
        // Elimino la condizione OR o AND dall'inizio della stringa
        $whereAnd = ltrim($whereAnd, ' OR ');
        $whereAnd = ltrim($whereAnd, ' AND ');
        $whereAnd = trim($whereAnd);
        if ($whereAnd) {
            $whereAnd = " AND ({$whereAnd})";
        }
        $whereOr = ltrim($whereOr, ' OR ');
        $whereOr = ltrim($whereOr, ' AND ');
        $whereOr = trim($whereOr);
        if ($whereOr) {
            $whereOr = " AND ({$whereOr})";
        }

        return "1 {$whereAnd} {$whereOr}";
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
