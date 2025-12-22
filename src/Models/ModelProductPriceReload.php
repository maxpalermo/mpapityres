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

use \Context;
use \Db;
use \DbQuery;
use \Tools;

class ModelProductPriceReload extends \ObjectModel
{
    public $price_min;
    public $price_max;
    public $reload_amount;
    public $reload_perc;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'product_price_reload',
        'primary' => 'id_product_price_reload',
        'fields' => [
            'price_min' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'price_max' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'reload_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false],
            'reload_perc' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $tablename = self::$definition['table'];

        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}{$tablename}` (
                `id_product_price_reload` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `price_min` decimal(20,6) UNSIGNED NOT NULL,
                `price_max` decimal(20,6) UNSIGNED NOT NULL,
                `reload_amount` decimal(20,6) UNSIGNED NOT NULL,
                `reload_perc` decimal(5,2) UNSIGNED NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_product_price_reload`)
            ) ENGINE=InnoDB
        ";

        return Db::getInstance()->execute($QUERY);
    }

    public static function getPriceReload()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $limit = (int) Tools::getValue('limit', 25);
        $offset = (int) Tools::getValue('offset', 0);
        $search = pSQL(Tools::getValue('search'));
        $sort = Tools::getValue('sort', 'a.id_product_price_reload');
        $order = Tools::getValue('order', 'asc');
        $filter_json = json_decode(Tools::getValue('filter'), true);
        $filters = [];

        if (is_array($filter_json) && !empty($filter_json)) {
            $filters = [
                'id_product_price_reload' => $filter_json['id_product_price_reload'] ?? '',
                'price_min' => $filter_json['price_min'] ?? '',
                'price_max' => $filter_json['price_max'] ?? '',
                'reload_amount' => $filter_json['reload_amount'] ?? '',
                'reload_perc' => $filter_json['reload_perc'] ?? '',
            ];
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
                        case 'id_product_price_reload':
                            $filter = (int) $filter;
                            $where_str = "a.{$key} = {$filter}";
                            break;
                        case 'price_min':
                        case 'price_max':
                        case 'reload_amount':
                            $filter = (float) str_replace(',', '.', (string) $filter);
                            $filterStr = rtrim(rtrim(sprintf('%.2F', $filter), '0'), '.');
                            $where_str = "a.{$key} = {$filterStr}";
                            break;
                        case 'reload_perc':
                            $filter = pSQL((string) $filter);
                            $where_str = "al.name LIKE '%{$filter}%'";
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
            ->select('count(id_product_price_reload) as total')
            ->from('product_price_reload', 'a');

        $applyWhere($sqlCount);
        $totalRows = (int) $db->getValue($sqlCount);

        $sql = new DbQuery();
        $sql
            ->select('
                a.id_product_price_reload,
                a.price_min,
                a.price_max,
                a.reload_amount,
                a.reload_perc,
                a.date_add,
                a.date_upd')
            ->from('product_price_reload', 'a')
            ->orderBy("{$sort} {$order}")
            ->limit($limit, $offset);

        $applyWhere($sql);

        $sql = $sql->build();

        $list = $db->executeS($sql);
        if ($list) {
            foreach ($list as &$row) {
                $row['total_products'] = rand(1000, 9999);
            }
        }

        return [
            'rows' => $list,
            'total' => $totalRows,
            'totalNotFiltered' => $totalRows,
        ];
    }

    public function updatePrice()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('a.id_product_price_reload')
            ->from('product_price_reload', 'a')
            ->where('price_min = ' . $this->price_min)
            ->where('price_max = ' . $this->price_max);
        $id = (int) $db->getValue($sql);

        $model = new self($id);
        $model->price_min = $this->price_min;
        $model->price_max = $this->price_max;
        $model->reload_amount = $this->reload_amount;
        $model->reload_perc = $this->reload_perc;

        if (\Validate::isLoadedObject($model)) {
            $model->date_upd = date('Y-m-d H:i:s');
            return $model->update();
        }

        return $model->add();
    }
}
