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

class LoadPriceHelper
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
            END,
            load_amount = {load_amount},
            load_perc = {load_perc},
            price_unit_loaded = CASE
                WHEN {load_amount} > 0 THEN 
                    (price_unit + {load_amount}) * (1 + ({load_perc} / 100))
                ELSE 
                    price_unit * (1 + ({load_perc} / 100))
            END,
            price_set_loaded = CASE
                WHEN {load_amount} > 0 THEN 
                    (price_set + {load_amount}) * (1 + ({load_perc} / 100))
                ELSE 
                    price_set * (1 + ({load_perc} / 100))
            END
        WHERE 
            content IS NOT NULL 
            AND JSON_VALID(content);
    ";

    public const QUERY_SELECT_PRICE_DIFF = "
        SELECT i.id_image, p.id_product, p.reference, p.price, pt.load_amount, pt.load_perc, pt.price_unit_loaded
        FROM {pfx}product p
        INNER JOIN {pfx}product_tyre pt ON (pt.id_t24=p.id_product and pt.type='API')
        LEFT JOIN {pfx}image i ON (i.id_product = p.id_product and i.cover = 1)
        WHERE p.price != pt.price_unit_loaded
        ORDER BY p.reference;
    ";

    public static function updateLoadPrices()
    {
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        $load_amount = (float) \Configuration::get('MPAPITYRES_RICARICO_PREZZO_DEFAULT');
        $load_perc = (float) \Configuration::get('MPAPITYRES_RICARICO_DEFAULT');
        $query = str_replace(['{pfx}', '{load_amount}', '{load_perc}'], [$pfx, $load_amount, $load_perc], self::QUERY_UPDATE_PRICE);

        try {
            $result = $db->execute($query);
            if ($result) {
                return $db->Affected_Rows();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . "\n" . $query);
        }

        return false;
    }

    public static function reloadPrices(array $rows, int $id_lang)
    {
        $total = count($rows);
        $count = 0;

        foreach ($rows as $row) {
            $product = new \Product($row['id_product'], false, $id_lang);
            if (!\Validate::isLoadedObject($product)) {
                continue;
            }

            $product->price = $row['price_unit_loaded'];
            $product->update();
            $count++;
        }

        $message = "Applicato il ricarico su {$count} prodotti su un totale di {$total}";

        return $message;
    }

    public static function getDiffPrices()
    {
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        $query = str_replace('{pfx}', $pfx, self::QUERY_SELECT_PRICE_DIFF);
        $list = $db->executeS($query);

        if ($list) {
            return $list;
        }

        return [];
    }
}
