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

namespace Modules\Mpapityres\Src\Helpers;

class LoadPriceHelper
{
    public const QUERY_UPDATE_PRICE = "
        UPDATE {pfx}product_tyre 
        SET 
            price_unit = CASE 
                WHEN JSON_EXTRACT(content, '$.price_1') IS NOT NULL 
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_1')) != ''
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_1')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_1')) AS DECIMAL(20,6))
                ELSE 0 
            END,
            price_set = CASE 
                WHEN JSON_EXTRACT(content, '$.price_4') IS NOT NULL 
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_4')) != ''
                AND JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_4')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.price_4')) AS DECIMAL(20,6))
                ELSE 0 
            END
        WHERE 
            content IS NOT NULL 
            AND JSON_VALID(content);
    ";
}