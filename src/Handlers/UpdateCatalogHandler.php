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

class UpdateCatalogHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $newRows = 0;
    private $updatedRows = 0;
    private $productImageHandler;
    private $features;
    private $suppliers;
    private $defaultCategoryId;
    private $taxRulesGroupId;
    private $errors = [];

    public function __construct()
    {
        parent::__construct();
        $this->productImageHandler = new ProductImageHandler();
        $this->features = $this->getFeaturesArray();
        $this->suppliers = $this->getSuppliersArray();
        $this->defaultCategoryId = $this->getDefaultCategory();
        $this->taxRulesGroupId = $this->getTaxRulesGroup();
    }

    public function updateCatalog(array $list): bool
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        foreach ($list as $tyre) {
            $reference = $tyre['matchcode'];
            try {
                $id_product = (int) $connection->executeQuery("SELECT id_product FROM {$pfx}product WHERE reference = :reference", ['reference' => $reference])->fetchOne();
            } catch (\Throwable $th) {
                $id_product = 0;
            }

            if ((int) $id_product) {
                $product = new \Product($id_product, false);
                $this->updateSupplier($id_product, $tyre);
                $this->updatePrice($id_product, $tyre['price_1'] ?? 0);
                $this->updateQuantity($product, $tyre['availability'], true);
                $this->updatedRows++;
            } else {
                $this->addProduct($tyre);
                $this->newRows++;
            }
        }

        return true;
    }

    public function getSuccess()
    {
        return true;
    }

    public function getNewRows()
    {
        return $this->newRows;
    }

    public function getUpdatedRows()
    {
        return $this->updatedRows;
    }

    private function updateSupplier($id_product, $tyre): void
    {
        $supplier = $this->getSupplier($tyre['brandname']);
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $connection->executeQuery(
            "
                UPDATE
                    {$pfx}product
                SET
                    id_supplier = :id_supplier
                WHERE
                    id_product = :id_product",
            [
                'id_supplier' => (int) $supplier->id,
                'id_product' => (int) $id_product
            ]
        );
    }

    private function updateCategories(\Product $product)
    {
        $product->updateCategories([$this->defaultCategoryId]);
    }

    private function updatePrice($id_product, $price): bool
    {
        $connection = $this->connection;

        $pfx = _DB_PREFIX_;
        $connection->executeQuery(
            "
                UPDATE
                    {$pfx}product
                SET
                    price = :price
                WHERE
                    id_product = :id_product",
            [
                'price' => (float) $price,
                'id_product' => (int) $id_product
            ]
        );

        if ($price == 0) {
            $connection->executeQuery(
                "
                    UPDATE
                        {$pfx}product
                    SET
                        active = 0
                    WHERE
                        id_product = :id_product",
                [
                    'id_product' => (int) $id_product
                ]
            );
        }

        return true;
    }

    private function addProduct($tyre): bool
    {
        $id_default_category = $this->defaultCategoryId;
        $id_tax_rules_group = $this->taxRulesGroupId;
        $supplier = $this->getSupplier($tyre['brandname']);
        $languages = \Language::getLanguages();
        $product = new \Product();
        $product->reference = $tyre['matchcode'];

        foreach ($languages as $language) {
            $product->name[$language['id_lang']] = strtoupper($tyre['articlename']);
            $product->description_short[$language['id_lang']] = strtoupper($tyre['description']);
            $product->description[$language['id_lang']] = '';
            $product->meta_title[$language['id_lang']] = '';
            $product->meta_description[$language['id_lang']] = '';
            $product->meta_keywords[$language['id_lang']] = '';
        }

        $product->id_category_default = $id_default_category;
        $product->id_tax_rules_group = $id_tax_rules_group;
        $product->ean13 = $tyre['ean13'] ?? '';
        $product->quantity = $tyre['availability'];
        $product->price = $tyre['price_1'];
        $product->id_supplier = $supplier->id;
        $product->supplier_name = strtoupper($supplier->name);
        $product->supplier_reference = strtoupper($tyre['manufacturer_number']);
        $product->location = '';
        $product->width = $tyre['width'];
        $product->height = $tyre['height'];
        $product->depth = $tyre['outer_diameter'];
        $product->active = 1;
        $product->show_condition = 1;
        $product->show_price = 1;
        $product->condition = "new";
        $product->visibility = "both";
        $product->product_type = "standard";
        $product->save();

        $this->updateQuantity($product, $tyre['availability'], true);
        //$this->addProductImages($product, $tyre);
        $this->addFeatures($product, $tyre);
        $this->updateCategories($product);

        return true;
    }

    public function downloadImages($tyres)
    {
        $this->updatedRows = 0;
        $this->errors = [];
        foreach ($tyres as $tyre) {
            $id_product = \Product::getIdByReference($tyre['matchcode']);
            if ($id_product) {
                $product = new \Product($id_product);
                $this->addProductImages($product, $tyre);
                $this->updatedRows++;
            } else {
                $this->errors[] = "Prodotto {$tyre['matchcode']} non trovato";
            }
        }
    }

    public function getSupplier($name)
    {
        foreach ($this->getSuppliersArray() as $supplier) {
            if ($supplier['name'] == $name) {
                return new \Supplier($supplier['id_supplier']);
            }
        }

        $supplier = new \Supplier();
        $supplier->name = $name;
        $supplier->active = 1;
        $supplier->add();
        $this->suppliers[] = [
            'id_supplier' => $supplier->id,
            'name' => $supplier->name
        ];

        return new \Supplier($supplier->id);
    }

    public function getTaxRulesGroup()
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $id_tax_rules_group = (int) $connection->executeQuery(
            "
                SELECT
                    id_tax_rules_group
                FROM
                    {$pfx}tax_rules_group
                WHERE
                    name = :name
            ",
            [
                'name' => 'IT Standard Rate (22%)'
            ]
        )->fetchOne();

        if ($id_tax_rules_group) {
            return $id_tax_rules_group;
        }

        return 0;
    }

    public function getDefaultCategory()
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $id_default_category = (int) $connection->executeQuery(
            "
                SELECT
                    id_category
                FROM
                    {$pfx}category_lang
                WHERE
                    name = :name
                    AND
                    id_lang = :id_lang
            ",
            [
                'name' => 'Pneumatici',
                'id_lang' => \Context::getContext()->language->id
            ]
        )->fetchOne();

        if ($id_default_category) {
            return $id_default_category;
        }

        return (int) \Configuration::get('PS_HOME_CATEGORY');
    }

    public function addProductImages(\Product $product, $tyre): void
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $row = $connection->executeQuery(
            "
                SELECT
                    *
                FROM
                    {$pfx}product_tyre_image
                WHERE
                    reference = :reference
            ",
            [
                'reference' => $tyre['matchcode']
            ]
        )->fetchAssociative();

        if (!$row) {
            return;
        }

        // Immagine principale
        if (!empty($row['image'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['image']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        // Altre immagini (es: image_tn, tyrelabel_link)
        if (!empty($row['image_tn'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['image_tn']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        if (!empty($row['tyrelabel_link'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['tyrelabel_link']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
    }

    public function addProductImages_old(\Product $product, $tyre): void
    {
        // Immagine principale
        if (!empty($tyre['image'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['image']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        // Altre immagini (es: image_tn, tyrelabel_link)
        if (!empty($tyre['image_tn'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['image_tn']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
        if (!empty($tyre['tyrelabel_link'])) {
            try {
                $this->productImageHandler->addProductImage($product, $tyre['tyrelabel_link']);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error adding product image: ' . $th->getMessage());
            }
        }
    }

    public function addFeatures(\Product $product, $tyre): void
    {
        $default_features = AdminMpApiTyresController::getFeaturesList();
        foreach ($default_features as $key => $feat) {
            $featureName = $feat;
            $featureValue = $tyre[$key] ?? '';
            if ($featureValue) {
                $feature = $this->setFeature($featureName, $featureValue);
                $product->addFeaturesToDB((int) $feature['id_feature'], (int) $feature['id_feature_value']);
            }
        }
    }

    public function setFeature($feature, $featureValue)
    {
        $result = [
            'id_feature' => null,
            'id_feature_value' => null
        ];
        $languages = \Language::getLanguages();
        $id_lang = (int) \Context::getContext()->language->id;
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;

        $existsFeatureQuery = "
            SELECT
                id_feature
            FROM
                {$pfx}feature_lang
            WHERE
                name = :feature
                AND
                id_lang = :id_lang
        ";

        $existsFeature = (int) $connection->executeQuery(
            $existsFeatureQuery,
            [
                'feature' => $feature,
                'id_lang' => $id_lang
            ]
        )->fetchOne();

        if ($existsFeature) {
            $result['id_feature'] = $existsFeature;
        } else {
            $featureObj = new \Feature();
            foreach ($languages as $language) {
                $featureObj->name[$language['id_lang']] = $feature;
            }
            $featureObj->position = \Feature::getHigherPosition() + 1;
            $featureObj->add();
            $result['id_feature'] = $featureObj->id;
        }

        $existsFeatureValueQuery = "
            SELECT
                a.id_feature_value
            FROM
                {$pfx}feature_value a
            INNER JOIN 
                {$pfx}feature_value_lang b
            ON
                a.id_feature_value = b.id_feature_value AND b.id_lang = :id_lang
            WHERE
                a.id_feature = :id_feature
                AND
                b.value = :feature_value
        ";

        $existsFeatureValue = (int) $connection->executeQuery(
            $existsFeatureValueQuery,
            [
                'id_feature' => $result['id_feature'],
                'id_lang' => $id_lang,
                'feature_value' => $featureValue
            ]
        )->fetchOne();

        if ($existsFeatureValue) {
            $result['id_feature_value'] = $existsFeatureValue;
        } else {
            $featureValueObj = new \FeatureValue();
            $featureValueObj->id_feature = $result['id_feature'];
            foreach ($languages as $language) {
                $featureValueObj->value[$language['id_lang']] = strtoupper($featureValue);
            }
            $featureValueObj->add();
            $result['id_feature_value'] = $featureValueObj->id;
        }

        return $result;
    }

    public function updateQuantity(\Product $product, $quantity, $setQuantity = false): void
    {
        if ($setQuantity) {
            \StockAvailable::setQuantity($product->id, 0, $quantity);
            $product->quantity += $quantity;
            $product->update();
        } else {
            \StockAvailable::updateQuantity($product->id, 0, $quantity);
            $product->quantity += $quantity;
            $product->update();
        }
    }

    public function getSuppliersArray()
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $suppliersQuery = "
            SELECT
                id_supplier, name
            FROM
                {$pfx}supplier
        ";
        $suppliers = $connection->executeQuery($suppliersQuery)->fetchAllAssociative();
        $this->suppliers = $suppliers;

        return $suppliers;
    }

    public function getFeaturesArray()
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $featuresQuery = "
            SELECT
                a.id_feature,b.name as feature, c.id_feature_value, d.value as feature_value
            FROM
                {$pfx}feature a
            INNER JOIN
                {$pfx}feature_lang b
            ON
                a.id_feature = b.id_feature AND b.id_lang = :id_lang
            INNER JOIN
                {$pfx}feature_value c
            ON
                a.id_feature = c.id_feature
            INNER JOIN
                {$pfx}feature_value_lang d
            ON
                c.id_feature_value = d.id_feature_value AND d.id_lang = :id_lang
            ORDER BY
                a.id_feature ASC,
                c.id_feature_value ASC
        ";
        $features = $connection->executeQuery($featuresQuery, [
            'id_lang' => (int) \Context::getContext()->language->id
        ])->fetchAllAssociative();

        $this->features = $features;

        return $features;
    }

    public function getFeatureId($featureName, $featureValue)
    {
        $features = $this->features;
        foreach ($features as $feature) {
            if ($feature['feature'] == $featureName && $feature['feature_value'] == $featureValue) {
                return [
                    'id_feature' => $feature['id_feature'],
                    'id_feature_value' => $feature['id_feature_value']
                ];
            }
        }

        $featureId = $this->getFeatureIdByName($featureName);
        $featureValueId = $this->getFeatureValueIdByName($featureId, $featureValue);

        return [
            'id_feature' => $featureId,
            'id_feature_value' => $featureValueId
        ];
    }

    public function getFeatureIdByName($featureName)
    {
        $languages = \Language::getLanguages();
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $featureQuery = "
            SELECT
                id_feature
            FROM
                {$pfx}feature_lang
            WHERE
                name = :featureName
                AND
                id_lang = :id_lang
        ";
        $featureId = (int) $connection->executeQuery($featureQuery, [
            'featureName' => $featureName,
            'id_lang' => (int) \Context::getContext()->language->id
        ])->fetchOne();

        if (!$featureId) {
            $feature = new \Feature();
            foreach ($languages as $language) {
                $feature->name[$language['id_lang']] = $featureName;
            }
            $feature->position = \Feature::getHigherPosition() + 1;
            $feature->add();
            $featureId = $feature->id;
            $this->features[] = [
                'id_feature' => $featureId,
                'feature' => $featureName
            ];
        }

        return $featureId;
    }

    public function getFeatureValueIdByName($featureId, $featureValue)
    {
        $languages = \Language::getLanguages();
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $featureValueQuery = "
            SELECT
                a.id_feature_value
            FROM
                {$pfx}feature_value_lang a
            INNER JOIN
                {$pfx}feature_value b
            ON
                a.id_feature_value = b.id_feature_value
            WHERE
                b.id_feature = :featureId
                AND
                a.value = :featureValue
                AND
                a.id_lang = :id_lang
        ";
        $featureValueId = (int) $connection->executeQuery($featureValueQuery, [
            'featureId' => $featureId,
            'featureValue' => $featureValue,
            'id_lang' => (int) \Context::getContext()->language->id
        ])->fetchOne();

        if (!$featureValueId) {
            $featureValue = new \FeatureValue();
            $featureValue->id_feature = $featureId;
            foreach ($languages as $language) {
                $featureValue->value[$language['id_lang']] = $featureValue;
            }
            $featureValue->add();
            $featureValueId = $featureValue->id;
            foreach ($this->features as $feature) {
                if ($feature['id_feature'] == $featureId) {
                    $feature['id_feature_value'] = $featureValueId;
                    $feature['feature_value'] = $featureValue;
                    break;
                }
            }
        }

        return $featureValueId;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function updateCatalogApi(array $list): array
    {
        $result = [];
        foreach ($list as $tyre) {
            $result[] = $this->updateProductTyre($tyre);
        }

        return $result;
    }
}