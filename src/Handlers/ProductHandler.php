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

use MpSoft\MpApiTyres\Handlers\ProductImageHandler;

class ProductHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $productImageHandler;
    private $featuresList;

    public function __construct($featuresList)
    {
        parent::__construct();
        $this->productImageHandler = new ProductImageHandler();
        $this->featuresList = $featuresList;
    }

    /**
     * Crea un nuovo prodotto PrestaShop 8 a partire dai dati di una riga della tabella product_tyre
     * @param array $tyreData
     * @param int $idLang
     * @param int $idShop
     * @return int|null  ID prodotto creato o null in caso di errore
     */
    public function createProductFromTyre(array $tyreData, $idLang = null, $idShop = null)
    {
        // Carica le classi core
        if (!class_exists('Product')) {
            require_once _PS_ROOT_DIR_ . '/classes/Product.php';
        }
        if (!class_exists('Category')) {
            require_once _PS_ROOT_DIR_ . '/classes/Category.php';
        }
        if (!class_exists('Image')) {
            require_once _PS_ROOT_DIR_ . '/classes/Image.php';
        }
        if (!class_exists('Shop')) {
            require_once _PS_ROOT_DIR_ . '/classes/shop/Shop.php';
        }

        $context = \Context::getContext();
        $idLang = $idLang ?: $context->language->id;
        $idShop = $idShop ?: $context->shop->id;

        $product = new \Product();

        // Nome prodotto
        $name = $tyreData['articlename'] ?? 'Pneumatico';
        $product->name = [$idLang => $name];

        // Descrizione breve e completa
        $product->description_short = [$idLang => $tyreData['description'] ?? $name];
        $product->description = [$idLang => $tyreData['manufacturer_description'] ?? $tyreData['description'] ?? $name];

        // Codice riferimento e EAN/UPC
        $product->reference = $tyreData['matchcode'] ?? '';
        $product->ean13 = \Validate::isEan13($tyreData['manufacturer_number']) ? $tyreData['manufacturer_number'] : '';

        // Prezzo
        $product->price = $tyreData['price_1'] ?? 0;
        $product->wholesale_price = $tyreData['avg_price'] ?? 0;

        // Marca
        if (isset($tyreData['brandname'])) {
            $product->manufacturer_name = $tyreData['brandname'];
        }

        // Categoria default (puoi personalizzare l'ID)
        $defaultCategoryId = (int) \Configuration::get('PS_HOME_CATEGORY');
        $product->id_category_default = $defaultCategoryId;
        $product->category = [$defaultCategoryId];
        $product->id_tax_rules_group = 1; // Default tax group

        // Quantità e visibilità
        $product->quantity = (int) ($tyreData['availability'] ?? 0);
        $product->active = 1;
        $product->show_price = 1;
        $product->visibility = 'both';

        // Attributi tecnici come features (se vuoi mapparli)
        // Puoi implementare qui la creazione/associazione delle features
        // Esempio: $this->addFeature($product->id, 'Larghezza', $tyreData['width']);

        // Salva il prodotto
        if (!$product->add()) {
            return null;
        }

        // Collega categorie
        $product->addToCategories([$defaultCategoryId]);

        // Immagine principale
        if (!empty($tyreData['image'])) {
            try {
                $this->productImageHandler->addProductImage($product->id, $tyreData['image']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        // Altre immagini (es: image_tn, tyrelabel_link)
        if (!empty($tyreData['image_tn'])) {
            try {
                $this->productImageHandler->addProductImage($product->id, $tyreData['image_tn']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        if (!empty($tyreData['tyrelabel_link'])) {
            try {
                $this->productImageHandler->addProductImage($product->id, $tyreData['tyrelabel_link']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }

        // Salva di nuovo per sicurezza
        $product->save();

        return $product->id;
    }

    // Esempio di aggiunta feature (opzionale, da implementare se vuoi mappare le caratteristiche)

    public function addFeatures($idProduct, $row)
    {
        $featuresList = $this->featuresList;
        foreach ($featuresList as $feature) {
            $featureName = $feature['name'];
            $featureValue = $row[$feature['name']];
            $this->addFeature($idProduct, $featureName, $featureValue);
        }
    }


}