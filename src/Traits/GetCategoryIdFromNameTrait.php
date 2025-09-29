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

namespace MpSoft\MpApiTyres\Traits;

trait GetCategoryIdFromNameTrait
{
    public static function getIdCategoryFromName($name)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $name = str_replace("'", "''", $name);
        $name = pSQL($name);

        $query->select("id_category")
            ->from('category_lang')
            ->where("name = '{$name}'")
            ->where("id_lang = {$id_lang}");

        return (int) $db->getValue($query);
    }
}
