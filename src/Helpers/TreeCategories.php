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

use Context;
use Category;
use HelperTreeCategories;

class TreeCategories
{
    /**
     * Render a tree of categories using HelperTreeCategories
     *
     * @param string $id Unique identifier for the tree
     * @param string|null $title Title of the tree
     * @param int|null $rootCategory Root category ID (null for default root)
     * @param int|null $idLang Language ID (null for current language)
     * @param bool $useSearch Enable search functionality
     * @param bool $useCheckbox Use checkboxes instead of radio buttons
     * @param bool $fullTree Display full tree or only selected branches
     * @param array $selectedCategories Array of selected category IDs
     * @param bool $useShopRestriction Apply shop restrictions
     *
     * @return string HTML rendering of the category tree
     */
    public static function renderTreeCategories(
        $id = 'categories',
        $title = null,
        $rootCategory = null,
        $idLang = null,
        $useSearch = true,
        $useCheckbox = false,
        $fullTree = false,
        $selectedCategories = [],
        $useShopRestriction = true
    ) {
        $context = Context::getContext();
        
        // Set default language if not provided
        if ($idLang === null) {
            $idLang = (int) $context->language->id;
        }
        
        // Set default root category if not provided
        if ($rootCategory === null) {
            $rootCategory = Category::getRootCategory($idLang)->id;
        }
        
        // Set default title if not provided
        if ($title === null) {
            $title = 'Elenco Categorie';
        }
        
        // Create HelperTreeCategories instance
        $helperTree = new HelperTreeCategories(
            $id,
            $title,
            $rootCategory,
            $idLang,
            $useShopRestriction
        );
        
        // Configure the helper
        $helperTree->setUseSearch($useSearch);
        $helperTree->setUseCheckBox($useCheckbox);
        $helperTree->setFullTree($fullTree);
        
        // Set selected categories if provided
        if (!empty($selectedCategories)) {
            $helperTree->setSelectedCategories($selectedCategories);
        }
        
        // Render and return the tree
        return $helperTree->render();
    }
}
