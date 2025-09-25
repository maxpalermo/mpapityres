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

use MpSoft\MpApiTyres\Models\ProductTyre;

class CreateInsertQueryFromJson
{
    public function __invoke(ProductTyre $productTyre)
    {
        $query = <<<QUERY
            INSERT INTO product_tyre (
                matchcode, outer_diameter, height, width, tyre_type, inner_diameter, brandname, profile, articlename, description, manufacturer_number, usage, type, load_index, speed_index, runflat, ms, three_pmfs, da_decke, price_1, price_4, avg_price, price_anonym, price_rvo, availability, expected_delivery_date, direct_link, info, image, image_tn, tyrelabel_link, energy_efficiency_index, wet_grip_index, noise_index, noise_decible, vehicle_class, ice_grip, data_sheet, demo, dot, dot_year, price_anonym_one, manufacturer_description
            ) VALUES (
                :matchcode, :outer_diameter, :height, :width, :tyre_type, :inner_diameter, :brandname, :profile, :articlename, :description, :manufacturer_number, :usage, :type, :load_index, :speed_index, :runflat, :ms, :three_pmfs, :da_decke, :price_1, :price_4, :avg_price, :price_anonym, :price_rvo, :availability, :expected_delivery_date, :direct_link, :info, :image, :image_tn, :tyrelabel_link, :energy_efficiency_index, :wet_grip_index, :noise_index, :noise_decible, :vehicle_class, :ice_grip, :data_sheet, :demo, :dot, :dot_year, :price_anonym_one, :manufacturer_description
            )
        QUERY;
    }

    public static function buildBulkInsertQuery(array $productTyres, int $batchSize = 1000)
    {
        $tableName = _DB_PREFIX_ . 'product_tyre';

        if (empty($productTyres)) {
            return '';
        }

        $columns = [
            'matchcode',
            'outer_diameter',
            'height',
            'width',
            'tyre_type',
            'inner_diameter',
            'brandname',
            'profile',
            'articlename',
            'description',
            'manufacturer_number',
            'usage',
            'type',
            'load_index',
            'speed_index',
            'runflat',
            'ms',
            'three_pmfs',
            'da_decke',
            'price_1',
            'price_4',
            'avg_price',
            'price_anonym',
            'price_rvo',
            'availability',
            'expected_delivery_date',
            'direct_link',
            'info',
            'image',
            'image_tn',
            'tyrelabel_link',
            'energy_efficiency_index',
            'wet_grip_index',
            'noise_index',
            'noise_decible',
            'vehicle_class',
            'ice_grip',
            'data_sheet',
            'demo',
            'dot',
            'dot_year',
            'price_anonym_one',
            'manufacturer_description'
        ];

        $column_list = array_map(fn($col) => "`$col`", $columns);
        $imploded_list = implode(',', $column_list);

        $length = count($productTyres);
        $batchSize = min($batchSize, $length);

        $query = "
            INSERT INTO
                $tableName
                ($imploded_list)
            VALUES
        ";

        $values = [];
        $queries = [];

        do {
            $query = "
                INSERT INTO
                    $tableName
                    ($imploded_list)
                VALUES
            ";

            $batch = array_splice($productTyres, 0, $batchSize);
            if (!$batch) {
                break;
            }

            //Elimino tutte le righe con campo demo=1
            $batch = array_filter($batch, fn($productTyre) => (int) $productTyre['demo'] !== (int) 1);

            /*
            //Elimino tutte le righe con campo price_4=0 o price_4=null
            $batch = array_filter($batch, fn($productTyre) => (float) $productTyre['price_4'] !== (float) 0);
            */

            //Elimino tutte le righe con quantità inferiore a 4
            $batch = array_filter($batch, fn($productTyre) => (int) $productTyre['availability'] >= 4);

            //Elimino tutte le righe che non hanno compilato il campo matchcode
            $batch = array_filter($batch, fn($productTyre) => !empty($productTyre['matchcode']));

            //Elimino tutte le righe con manufacturer_description che contiene "italien", "italy" o "italia"
            $italian = array_filter($batch, fn($productTyre) => str_contains(strtolower($productTyre['manufacturer_description']), 'italien') || str_contains(strtolower($productTyre['manufacturer_description']), 'italy') || str_contains(strtolower($productTyre['manufacturer_description']), 'italia'));
            $batch = array_filter($batch, fn($productTyre) => !str_contains(strtolower($productTyre['manufacturer_description']), 'italien') && !str_contains(strtolower($productTyre['manufacturer_description']), 'italy') && !str_contains(strtolower($productTyre['manufacturer_description']), 'italia'));

            // Colonne numeriche note (da aggiornare secondo la struttura reale)
            $numericColumns = [
                'price_1',
                'price_4',
                'avg_price',
                'price_anonym',
                'price_rvo',
                'noise_index',
                'noise_decible',
                'load_index',
                'speed_index',
                'availability',
                'runflat',
                'ms',
                'three_pmfs',
                'da_decke',
                'demo',
                'dot_year',
                // aggiungi qui altre colonne numeriche se necessario
            ];
            foreach ($batch as $productTyre) {
                $row = [];
                foreach ($columns as $col) {
                    $value = $productTyre[$col] ?? null;
                    if ($value === null || $value === '' || (in_array($col, $numericColumns) && !is_numeric($value))) {
                        $row[] = "NULL";
                    } else {
                        $escaped = addslashes($value);
                        $row[] = "'$escaped'";
                    }
                }
                $values[] = '    (' . implode(', ', $row) . ')';
            }
            $query .= implode(",\n", $values) . ';';
            $queries[] = $query;
            $values = [];
        } while ($batch);

        return $queries;
    }

    public static function buildNewTyreImagesQuery()
    {
        $pfx = _DB_PREFIX_;
        $query = "
            INSERT IGNORE INTO
                {$pfx}product_tyre_image (id_product, reference, image, image_tn, data_sheet, tyrelabel_link)
            SELECT
                '0' as id_product,
                pt.matchcode as reference,
                pt.image as image,
                pt.image_tn as image_tn,
                pt.data_sheet as data_sheet,
                pt.tyrelabel_link as tyrelabel_link
            FROM
                {$pfx}product_tyre pt
        ";

        return $query;
    }

    public static function buildUpdateTyreImagesQuery()
    {
        $pfx = _DB_PREFIX_;
        $query = "
            UPDATE
                {$pfx}product_tyre_image pti
            INNER JOIN
                {$pfx}product p
            ON
                pti.reference = p.reference
            SET
                pti.id_product = p.id_product
        ";

        return $query;
    }
}
