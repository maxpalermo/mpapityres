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

class ModelProductTyre extends \ObjectModel
{
    public const QUERY_UPDATE_PRICE = "
        UPDATE {pfx}product_tyre 
        SET 
            price_unit = CASE 
                WHEN JSON_EXTRACT(content, '\$.price_1') IS NOT NULL 
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_1')) != ''
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_1')) REGEXP '^-?[0-9]+(\.[0-9]+)?\$'
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_1')) AS DECIMAL(20,6))
                ELSE 0 
            END,
            price_set = CASE 
                WHEN JSON_EXTRACT(content, '\$.price_4') IS NOT NULL 
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_4')) != ''
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_4')) REGEXP '^-?[0-9]+(\.[0-9]+)?\$'
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '\$.price_4')) AS DECIMAL(20,6))
                ELSE 0 
            END
        WHERE 
            content IS NOT NULL 
            AND JSON_VALID(content);
    ";

    public const QUERY_UPDATE_LOAD_PRICE_PERC = '
        UPDATE `{pfx}product_tyre` SET `price_unit_loaded` = `price_unit` * (100 + `load_perc`) / 100;
        UPDATE `{pfx}product_tyre` SET `price_set_loaded` = `price_set` * (100 + `load_perc`) / 100;
    ';

    public const QUERY_UPDATE_LOAD_PRICE_AMOUNT = '
        UPDATE `{pfx}product_tyre` SET `price_unit_loaded` = `price_unit` + `load_amount`;
        UPDATE `{pfx}product_tyre` SET `price_set_loaded` = `price_set` + `load_amount`;
    ';

    public const QUERY_SELECT_PRICE_DIFF = "
        SELECT i.id_image, p.id_product, p.reference, p.price, pt.load_amount, pt.load_perc, pt.price_unit_loaded
        FROM {pfx}product p
        INNER JOIN {pfx}product_tyre pt ON (pt.id_t24=p.id_product and pt.type='API')
        LEFT JOIN {pfx}image i ON (i.id_product = p.id_product and i.cover = 1)
        WHERE p.price != pt.price_unit_loaded
        ORDER BY p.reference;
    ";

    public $type;
    public $matchcode;
    public $content;
    public $price_unit;
    public $price_set;
    public $load_amount;
    public $load_perc;
    public $price_unit_loaded;
    public $price_set_loaded;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mpapityres_product_tyre',
        'primary' => 'id_mpapityres_product_tyre',
        'fields' => [
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true],
            'matchcode' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true],
            'content' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true],
            'price_unit' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'price_set' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'load_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'load_perc' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'price_unit_loaded' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'price_set_loaded' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateOrNull', 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
        if ($this->content) {
            $this->content = json_decode($this->content, true);
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        return parent::update($null_values);
    }

    public function reloadPrice()
    {
        $product = new \Product($this->id);
        if ($product->price == $this->price_unit) {
            $price = $product->price;
            if ($this->load_amount) {
                $price = self::reload($product->price, $this->load_amount, 'amount');
            } elseif ($this->load_perc) {
                $price = self::reload($product->price, $this->load_perc, 'perc');
            }
            if ($price != $product->price) {
                $product->price = $price;
                $product->update();
            }
        }

        return false;
    }

    public static function reload($price, $load, $type)
    {
        if ($type == 'amount') {
            return $price + $load;
        }
        if ($type == 'perc') {
            return $price + ($price * ($load / 100));
        }
    }

    public static function getPriceListDiff()
    {
        $pfx = _DB_PREFIX_;
        $query = str_replace('{pfx}', $pfx, self::QUERY_SELECT_PRICE_DIFF);
        $db = \Db::getInstance();
        $list = $db->executeS($query);

        if ($list) {
            return $list;
        }

        return [];
    }
}
