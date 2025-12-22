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

use MpSoft\MpApiTyres\Helpers\Manufacturers;
use MpSoft\MpApiTyres\Helpers\PFU;
use MpSoft\MpApiTyres\Helpers\TyreWeight;
use \Context;
use \Db;
use \DbQuery;
use \Image;
use \Tools;

class ModelProductTyre extends \ObjectModel
{
    public const TYPE_TYRE = 'tyre';
    public const TYPE_ALLOY = 'alloy';
    public const TYPE_PFU = 'pfu';

    public $id_tyre;
    public $type_tyre;
    public $pfu_weight_min;
    public $pfu_weight_max;
    public $price;
    public $id_pfu_associated;
    protected static $error;

    public static $definition = [
        'table' => 'product_tyre',
        'primary' => 'id_product',
        'fields' => [
            'id_tyre' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'type_tyre' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false, 'size' => 16],
            'pfu_weight_min' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false],
            'pfu_weight_max' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false],
            'id_pfu_associated' => ['type' => self::TYPE_STRING, 'validate' => 'isUnsignedId', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS {$pfx}product_tyre (
            `id_product` INT(11) NOT NULL AUTO_INCREMENT,
            `id_tyre` INT(11) NOT NULL,
            `type_tyre` VARCHAR(16) NOT NULL,
            `pfu_weight_min` DECIMAL(9,2) NOT NULL,
            `pfu_weight_max` DECIMAL(9,2) NOT NULL,
            `price` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `id_pfu_associated` INT(11) UNSIGNED NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id_product`,`id_tyre`) USING BTREE
            ) ENGINE={$engine};
        ";

        return Db::getInstance()->execute($QUERY);
    }

    public static function addProduct($id_product, $id_tyre, $type_tyre, $pfu_weight_min, $pfu_weight_max, $price, $id_pfu_associated = 0, $force_update = false)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::$definition['table'];
        $id_tyre = (int) $id_tyre;
        $sql = "SELECT id_product FROM {$table} WHERE id_tyre = {$id_tyre}";

        $result = $db->getValue($sql);

        $data = [
            'id_tyre' => (int) $id_tyre,
            'type_tyre' => pSQL($type_tyre),
            'pfu_weight_min' => (float) $pfu_weight_min,
            'pfu_weight_max' => (float) $pfu_weight_max,
            'price' => (float) $price,
            'id_pfu_associated' => (int) $id_pfu_associated,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        try {
            if ($result && $force_update) {
                return $db->update(
                    'product_tyre',
                    $data,
                    'id_product = ' . (int) $id_product
                );
            }

            if ($result && !$force_update) {
                return true;
            }

            $data['id_product'] = (int) $id_product;

            return $db->insert(
                'product_tyre',
                $data,
                true,
                false,
                \DbCore::INSERT_IGNORE
            );
        } catch (\Throwable $th) {
            self::$error = $th->getMessage();
            return false;
        }
    }

    public static function getError()
    {
        return self::$error;
    }

    public static function clearError()
    {
        self::$error = null;
    }

    public static function getTaxRate($id_tax_rules_group, $id_address)
    {
        if ((int) $id_address === 0) {
            if (1 == 0) {
                // Forzo Italia
                $idCountryIt = (int) \Country::getByIso('IT');  // oppure fisso a mano l'ID se lo conosci

                $address = new \Address();
                $address->id_country = $idCountryIt;
                // opzionale, se le tue tax rules usano stato/CAP:
                // $address->id_state = 0;
                // $address->postcode = '';
            }

            $address = \Address::initialize(null);
        } else {
            $address = new \Address((int) $id_address);
        }

        $taxManager = \TaxManagerFactory::getManager($address, (int) $id_tax_rules_group);
        $taxCalculator = $taxManager->getTaxCalculator();

        return (float) $taxCalculator->getTotalRate();
    }

    /**
     * Get PFU list from CSV
     * Returns an array of arrays containing PFU data
     * Each inner array represents a row from the CSV file
     * Array Structure:
     *  - min_weight
     *  - max_weight
     *  - price
     * @return array<string[]>
     */
    public static function getPfuTableList()
    {
        $filename = _PS_MODULE_DIR_ . 'mpapityres/data/pfu_table.csv';
        $values = [];

        if (file_exists($filename)) {
            $handle = fopen($filename, 'r');
            if ($handle !== false) {
                while (true) {
                    $data = fgetcsv($handle, 0, ',');
                    if (!$data) {
                        break;
                    }
                    $values[] = array_map('trim', $data);
                }
            }
        }
        fclose($handle);

        $header = array_shift($values);
        $TABLE = [];
        foreach ($values as $value) {
            $TABLE[] = array_combine($header, $value);
        }

        return $TABLE;
    }

    public static function getPfuByMinMax($min, $max)
    {
        $pfx = _DB_PREFIX_;
        $table = "{$pfx}product_tyre";
        $query = "SELECT id_product FROM {$table} WHERE pfu_weight_min >= {$min} AND pfu_weight_max < {$max}";

        $result = Db::getInstance()->getValue($query);

        return $result;
    }

    /**
     * Create PFU products from the given data
     * @param array $data Array of PFU data
     * @return bool True if successful
     */
    public static function createPfuList($data)
    {
        foreach ($data as $pfu) {
            $id_product = self::getPfuByMinMax($pfu['min_weight'], $pfu['max_weight']);
            if (!$id_product) {
                // Product not found, create it
                PFU::createProductPfu($pfu);
            }
        }

        return true;
    }

    public static function getIdProductByIdTyre($id_tyre)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('id_product')
            ->from('product_tyre')
            ->where('id_tyre = ' . (int) $id_tyre);
        $result = (int) $db->getValue($sql);

        return $result;
    }

    public static function getOrCreateManufacturer($name, $id_tyre)
    {
        $languages = \Language::getLanguages();
        $id_manufacturer = (int) \Manufacturer::getIdByName($name);
        $brandName = $name;
        $brandImageUrl = '';
        $brandDescription = '';

        // Se esiste già, restituisco l'ID
        if ($id_manufacturer > 0) {
            return $id_manufacturer;
        }

        $product = null;  // GuzzleApi::getProduct($id_tyre);
        if ($product) {
            $brandName = $product['manufacturerName'];
            $brandImageUrl = $product['manufacturerImage'];
            $brandDescription = $product['manufacturerDescription'];
        }

        $manufacturer = new \Manufacturer();

        $manufacturer->name = strtoupper($brandName);
        $manufacturer->active = 1;

        foreach ($languages as $language) {
            $manufacturer->description[$language['id_lang']] = $brandDescription;
        }

        $result = $manufacturer->save();

        if ($result) {
            // Scarico l'immagine
            $id_manufacturer = (int) $manufacturer->id;

            $image = Manufacturers::getManufacturerImageByName($brandName);
            if ($image) {
                Manufacturers::addManufacturerImage($id_manufacturer, $image);
            }

            return $id_manufacturer;
        }

        return false;
    }

    public static function getPfu($id_product, $returnId = false)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('id_pfu_associated')
            ->from('product_tyre')
            ->where('id_product = ' . (int) $id_product)
            ->where("type_tyre = 'tyre'");

        $id_pfu = (int) $db->getValue($sql);

        if ($id_pfu) {
            if ($returnId) {
                return (int) $id_pfu;
            }
            return new self($id_pfu);
        }

        return false;
    }

    public static function isPfu($id_product)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('id_product')
            ->from('product_tyre')
            ->where('id_product = ' . (int) $id_product)
            ->where("type_tyre = 'pfu'");

        $id_product = (int) $db->getValue($sql);

        return $id_product > 0;
    }

    public static function applyPfuAssociation($idProduct, $idPfu)
    {
        $db = Db::getInstance();
        $result = $db->update(
            'product_tyre',
            ['id_pfu_associated' => (int) $idPfu],
            'id_product = ' . (int) $idProduct
        );

        return $result;
    }

    public static function applyPfuAssociationBulk($idProducts, $idPfu)
    {
        if (!is_array($idProducts) || empty($idProducts)) {
            return false;
        }

        $ids = array_values(array_filter(array_map('intval', $idProducts)));
        if (empty($ids)) {
            return false;
        }

        $db = Db::getInstance();
        $where = 'id_product IN (' . implode(',', $ids) . ')';

        return (bool) $db->update(
            'product_tyre',
            ['id_pfu_associated' => (int) $idPfu],
            $where
        );
    }

    public static function getPfuAssociations()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $limit = (int) Tools::getValue('limit', 25);
        $offset = (int) Tools::getValue('offset', 0);
        $search = pSQL(Tools::getValue('search'));
        $sort = Tools::getValue('sort', 'a.id_product');
        $order = Tools::getValue('order', 'asc');
        $filter_json = json_decode(Tools::getValue('filter'), true);
        $filters = [];

        if (is_array($filter_json) && !empty($filter_json)) {
            $filters = [
                'id_product' => $filter_json['id_product'] ?? '',
                'reference' => $filter_json['reference'] ?? '',
                'product_name' => $filter_json['product_name'] ?? '',
                'weight' => $filter_json['weight'] ?? '',
                'width' => $filter_json['width'] ?? '',
                'height' => $filter_json['height'] ?? '',
                'depth' => $filter_json['depth'] ?? '',
                'price' => $filter_json['price'] ?? '',
                'id_pfu_associated' => $filter_json['id_pfu_associated'] ?? '',
            ];
        }

        if ($sort == 'product_name') {
            $sort = 'al.name';
        }

        if ($sort == 'pfu_name') {
            $sort = 'bl.name';
        }

        if ($sort == 'id_pfu_associated') {
            $sort = 'bl.name';
        }

        $db = Db::getInstance();

        $applyWhere = function (DbQuery $sql) use ($filters, $search) {
            if ($search) {
                $sql->where("a.reference LIKE '%{$search}%' OR al.name LIKE '%{$search}%' OR bl.name LIKE '%{$search}%' ");
            }

            if ($filters) {
                foreach ($filters as $key => $filter) {
                    $filter = is_string($filter) ? trim($filter) : $filter;
                    if ($filter === '' || $filter === null) {
                        continue;
                    }

                    $where_str = '';
                    switch ($key) {
                        case 'id_product':
                            $filter = (int) $filter;
                            $where_str = "a.{$key} = {$filter}";
                            break;
                        case 'weight':
                        case 'width':
                        case 'height':
                        case 'depth':
                        case 'price':
                            $filter = (float) str_replace(',', '.', (string) $filter);
                            $filterStr = rtrim(rtrim(sprintf('%.2F', $filter), '0'), '.');
                            $where_str = "a.{$key} = {$filterStr}";
                            break;
                        case 'product_name':
                            $filter = pSQL((string) $filter);
                            $where_str = "al.name LIKE '%{$filter}%'";
                            break;
                        case 'reference':
                            $filter = pSQL((string) $filter);
                            $where_str = "a.reference LIKE '%{$filter}%'";
                            break;
                        case 'id_pfu_associated':
                            if (is_numeric($filter)) {
                                $filter = (int) $filter;
                                $where_str = "b.id_pfu_associated = {$filter}";
                            } else {
                                $filter = pSQL((string) $filter);
                                $where_str = "bl.name LIKE '%{$filter}%'";
                            }
                            break;
                        default:
                            break;
                    }

                    if ($where_str) {
                        $sql->where($where_str);
                    }
                }
            }
        };

        $sqlCount = new DbQuery();
        $sqlCount
            ->select('COUNT(a.id_product) as total')
            ->from('product', 'a')
            ->innerJoin('product_lang', 'al', "a.id_product=al.id_product AND al.id_lang={$id_lang}")
            ->leftJoin('product_tyre', 'b', "a.id_product=b.id_product AND b.type_tyre='tyre'")
            ->leftJoin('product_lang', 'bl', "b.id_pfu_associated=bl.id_product AND bl.id_lang={$id_lang}")
            ->where("b.type_tyre='tyre'")
            ->where('a.active = 1');

        $applyWhere($sqlCount);
        $totalRows = (int) $db->getValue($sqlCount);

        $sql = new DbQuery();
        $sql
            ->select('a.id_product, a.reference, al.name as product_name, a.weight, a.width, a.height, a.depth, a.price, b.id_pfu_associated, bl.name as pfu_name')
            ->from('product', 'a')
            ->innerJoin('product_lang', 'al', "a.id_product=al.id_product AND al.id_lang={$id_lang}")
            ->leftJoin('product_tyre', 'b', "a.id_product=b.id_product AND b.type_tyre='tyre'")
            ->leftJoin('product_lang', 'bl', "b.id_pfu_associated=bl.id_product AND bl.id_lang={$id_lang}")
            ->where("b.type_tyre='tyre'")
            ->orderBy("{$sort} {$order}")
            ->limit($limit, $offset);

        $applyWhere($sql);

        $sql = $sql->build();

        $list = $db->executeS($sql);
        if ($list) {
            foreach ($list as &$row) {
                /** @var array|bool $imageCoverId */
                $imageCoverId = Image::getCover((int) $row['id_product']);
                if ($imageCoverId) {
                    $imagePath = _PS_IMG_ . 'p/' . Image::getImgFolderStatic($imageCoverId['id_image']) . $imageCoverId['id_image'] . '.jpg';
                    $row['image'] = $imagePath;
                } else {
                    $row['image'] = _PS_IMG_ . '404.gif';
                }
            }
        }

        return [
            'rows' => $list,
            'total' => $totalRows,
            'totalNotFiltered' => $totalRows,
        ];
    }
}
