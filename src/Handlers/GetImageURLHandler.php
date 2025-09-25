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

namespace MpSoft\MpApiTyres\Handlers;

use MpSoft\MpApiTyres\Controllers\AdminMpApiTyresController;
use MpSoft\MpApiTyres\Models\ProductTyre;

class GetImageURLHandler
{
    public static function getImageUrl($reference)
    {
        $connection = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $pfx = _DB_PREFIX_;
        $image = $connection->getValue(
            "
                SELECT
                    image
                FROM
                    {$pfx}product_tyre_image
                WHERE
                    reference = '$reference'
            "
        );

        if (!$image) {
            return false;
        }

        return $image;
    }

    public static function getDataSheetUrl($reference)
    {
        $connection = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $pfx = _DB_PREFIX_;
        $dataSheet = $connection->getValue(
            "
                SELECT
                    data_sheet
                FROM
                    {$pfx}product_tyre_image
                WHERE
                    reference = '$reference'
            "
        );

        if (!$dataSheet) {
            return false;
        }

        return $dataSheet;
    }

    public static function getTyreLabelLink($reference)
    {
        $connection = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $pfx = _DB_PREFIX_;
        $tyreLabelLink = $connection->getValue(
            "
                SELECT
                    tyre_label_link
                FROM
                    {$pfx}product_tyre_image
                WHERE
                    reference = '$reference'
            "
        );

        if (!$tyreLabelLink) {
            return false;
        }

        return $tyreLabelLink;
    }

    public static function getDataRow($reference)
    {
        $connection = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $pfx = _DB_PREFIX_;
        $dataRow = $connection->getRow(
            "
                SELECT
                    *
                FROM
                    {$pfx}product_tyre_image
                WHERE
                    reference = '$reference'
            "
        );

        if (!$dataRow) {
            return false;
        }

        return $dataRow;
    }
}