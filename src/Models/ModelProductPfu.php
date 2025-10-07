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

class ModelProductPfu extends \ObjectModel
{
    public $id_pfu;
    public $price;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'product_pfu',
        'primary' => 'id_product',
        'fields' => [
            'id_pfu' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateOrNull'],
        ],
    ];

    public static function getPfuStatic()
    {
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;

        $query = "
            SELECT
                id_product, reference
            FROM
                {$pfx}product
            WHERE
                reference LIKE 'PFU%'
            ORDER BY
                id_product
            ";

        $list = $db->executeS($query);
        if ($list) {
            $pfu = [];
            foreach ($list as $row) {
                $parts = explode('-', $row['reference']);
                $start = $parts[1];
                $end = $parts[2];
                $price = $parts[3];

                $pfu[$row['id_product']] = [
                    'id_product' => $row['id_product'],
                    'reference' => $row['reference'],
                    'weightStart' => $start,
                    'weightEnd' => $end,
                    'price' => $price,
                ];
            }
        }

        return $pfu;
    }
}
