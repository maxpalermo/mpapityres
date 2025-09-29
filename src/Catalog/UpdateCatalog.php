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

namespace MpSoft\MpApiTyres\Catalog;
use MpSoft\MpApiTyres\Traits\DownloadImageFromUrlTrait;
use MpSoft\MpApiTyres\Traits\GetCategoryIdFromNameTrait;

class UpdateCatalog
{
    use DownloadImageFromUrlTrait;
    use GetCategoryIdFromNameTrait;

    protected $db;
    protected $languages;
    protected $default_lang_id;
    protected $errors;

    private const FORCE_UPLOAD_IMAGES = false;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->languages = \Language::getLanguages(false);
        $this->default_lang_id = (int) \Configuration::get('PS_LANG_DEFAULT');
        $this->errors = [];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Crea un array multilingua per i campi di un prodotto
     * 
     * @param string $value Valore da inserire per tutte le lingue
     * @return array Array multilingua
     */
    protected function createMultiLangField($value)
    {
        $result = [];
        foreach ($this->languages as $language) {
            if (is_array($value)) {
                $result[$language['id_lang']] = $value[$language['id_lang']] ?? reset($value);
            } else {
                $result[$language['id_lang']] = $value;
            }
        }
        return $result;
    }

    /**
     * Aggiorna o crea un prodotto in PrestaShop
     * 
     * @param array $product Dati del prodotto
     * @return array{success: bool, errors: array} Risultato dell'operazione
     */
    public function updateProduct($product)
    {
        $errors = [];
        try {
            $reference = $product['matchcode'] ?? uuid_create(UUID_TYPE_RANDOM);
            $id_product = (int) $product['idT24'];
            $id_category_default = (int) self::getIdCategoryFromName(\Configuration::get('MPAPITYRES_DEFAULT_CATEGORY'));
            if (!$id_category_default) {
                $id_category_default = (int) \Configuration::get("PS_HOME_CATEGORY");
            }

            // Verifica se il prodotto esiste già o se l'EAN è corretto
            $db = \Db::getInstance();
            $pfx = _DB_PREFIX_;
            $ean = $product['ean'] ?? '';
            if (!$ean) {
                return [
                    'success' => false,
                    'errors' => ['EAN13 non valido o inesistente'],
                ];
            }

            $sql = "SELECT id_product FROM {$pfx}product WHERE ean13 = '{$product['ean']}'";
            $id_product = $db->getValue($sql);
            if ($id_product) {
                $product = new \Product($id_product);
                $product->active = 1;
                $product->update();

                return [
                    'success' => false,
                    'errors' => ['Prodotto esistente']
                ];
            }

            // Creo o aggiorno il prodotto
            $id_product = (int) $product['idT24'];
            $prestashopProduct = new \Product($id_product);

            //Se il prodotto esiste non faccio niente
            if (\Validate::isLoadedObject($prestashopProduct)) {
                //Controllo se il prodotto contiene immagini
                $id_cover = \Image::getCover($prestashopProduct->id);
                if (!$id_cover || self::FORCE_UPLOAD_IMAGES) {
                    self::addProductImageStatic($prestashopProduct->id, $product['csv_image_url']);
                    self::addProductImageStatic($prestashopProduct->id, $product['csv_label_url']);
                }

                return [
                    'success' => true,
                    'id_product' => $prestashopProduct->id,
                    'reference' => $prestashopProduct->reference
                ];
            }

            // Dati base del prodotto
            $prestashopProduct->force_id = true;
            $prestashopProduct->id = $id_product;
            $prestashopProduct->reference = $reference;
            $prestashopProduct->name = $this->createMultiLangField($product['description'] ?? '');
            $prestashopProduct->description_short = $this->createDescription($product);
            //$prestashopProduct->description = $this->createMultiLangField($product['description'] ?? '');
            $prestashopProduct->link_rewrite = $this->createMultiLangField(
                \Tools::str2url($product['description'] ?? 'product-' . $reference)
            );

            // Prezzi e tasse
            $prestashopProduct->ean13 = (string) ($product['ean'] ?? '');
            $prestashopProduct->price = (float) ($product['price'] ?? 0);
            $prestashopProduct->wholesale_price = (float) ($product['wholesale_price'] ?? 0);
            $prestashopProduct->id_tax_rules_group = (int) ($product['id_tax_rules_group'] ?? 0);

            // Stock
            $prestashopProduct->quantity = (int) ($product['quantity'] ?? 0);
            $prestashopProduct->minimal_quantity = (int) ($product['minimal_quantity'] ?? 1);
            $prestashopProduct->low_stock_threshold = (int) ($product['low_stock_threshold'] ?? 0);
            $prestashopProduct->low_stock_alert = (bool) ($product['low_stock_alert'] ?? false);

            // Stato e visibilità
            $prestashopProduct->active = (bool) ($product['active'] ?? true);
            $prestashopProduct->visibility = $product['visibility'] ?? 'both'; // both, catalog, search, none
            $prestashopProduct->available_for_order = (bool) ($product['available_for_order'] ?? true);
            $prestashopProduct->show_price = (bool) ($product['show_price'] ?? true);

            // Categorie
            $prestashopProduct->id_category_default = $id_category_default;

            //Creo o Restituisco il produttore
            $manufacturer = [
                'name' => $product['manufacturerName'],
                'description' => $product['manufacturerDescription'],
                'image' => $product['manufacturerImage'],
            ];
            $id_manufacturer = $this->getOrCreateManufacturer($manufacturer);
            $prestashopProduct->id_manufacturer = $id_manufacturer;

            // Dimensioni e peso
            $prestashopProduct->width = (float) ($product['width'] ?? 0);
            $prestashopProduct->height = (float) ($product['height'] ?? 0);
            $prestashopProduct->depth = (float) ($product['depth'] ?? 0);
            $prestashopProduct->weight = (float) ($product['weight'] ?? 0);

            // Meta informazioni per SEO
            $prestashopProduct->meta_title = $this->createMultiLangField($product['meta_title'] ?? '');
            $prestashopProduct->meta_description = $this->createMultiLangField($product['meta_description'] ?? '');
            $prestashopProduct->meta_keywords = $this->createMultiLangField($product['meta_keywords'] ?? '');

            // Salvo il prodotto
            try {
                $result = $prestashopProduct->add();
            } catch (\Throwable $th) {
                $this->errors[] = "Errore {$th->getMessage()} durante il salvataggio del prodotto: {$reference}";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            if (!$result) {
                $this->errors[] = "Errore durante il salvataggio del prodotto: {$reference}";
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }
            $id_product = (int) $prestashopProduct->id;

            // Associo le categorie
            $categories = [$id_category_default];
            if (!empty($product['categories']) && is_array($product['categories'])) {
                $categories = array_merge($categories, $product['categories']);
            }
            $prestashopProduct->updateCategories(array_unique($categories));

            $product['features'] = [
                [
                    'name' => 'Stagione',
                    'value' => $product['type'] ?? null,
                ],
                [
                    'name' => 'Indice di velocita',
                    'value' => $product['speedIndex'] ?? null,
                ],
                [
                    'name' => 'Profilo',
                    'value' => $product['profileName'] ?? null,
                ],
                [
                    'name' => 'Grandezza',
                    'value' => $product['size'] ?? null,
                ],
                [
                    'name' => 'Indice energetico',
                    'value' => $product['tyreLabel']['energyIndex'] ?? null,
                ],
                [
                    'name' => 'Indice tenuta bagnato',
                    'value' => $product['tyreLabel']['wetGripIndex'] ?? null,
                ],
                [
                    'name' => 'Indice tenuta ghiaccio',
                    'value' => $product['tyreLabel']['iceGripIndex'] ?? null,
                ],
                [
                    'name' => 'Indice rumore',
                    'value' => $product['tyreLabel']['rollingNoiseIndex'] ?? null,
                ],
                [
                    'name' => 'Livello rumore',
                    'value' => $product['tyreLabel']['rollingNoiseLevel'] ?? null,
                ],
                [
                    'name' => 'Classe Veicolo',
                    'value' => $product['tyreLabel']['vehicleClass'] ?? null,
                ],
                [
                    'name' => '3PMSF',
                    'value' => $product['tyreLabel']['3pmsf'] ?? null,
                ],
            ];
            // Gestione delle caratteristiche
            if (!empty($product['features']) && is_array($product['features'])) {
                foreach ($product['features'] as $feature) {
                    if (empty($feature['name']) || empty($feature['value'])) {
                        continue;
                    }

                    // Cerco o creo la caratteristica
                    $id_feature = $this->getOrCreateFeature($feature['name']);

                    // Cerco o creo il valore della caratteristica
                    $id_feature_value = $this->getOrCreateFeatureValue($id_feature, $feature['value']);

                    // Associo la caratteristica al prodotto
                    $prestashopProduct->addFeaturesToDB($id_feature, $id_feature_value);
                }
            }

            // Gestione delle immagini
            if (!empty($product['csv_image_url'])) {
                self::addProductImageStatic($id_product, $product['csv_image_url']);
            }

            // Gestione dell'immagine del label
            if (!empty($product['csv_label_url'])) {
                self::addProductImageStatic($id_product, $product['csv_label_url']);
            }


            // Aggiorno la quantità di stock
            if (isset($product['stock'])) {
                \StockAvailable::setQuantity($id_product, 0, (int) $product['stock']);
            }

            return [
                'success' => true,
                'id_product' => $id_product,
                'reference' => $prestashopProduct->reference
            ];

        } catch (\Exception $e) {
            $errors[] = 'Errore durante l\'aggiornamento del prodotto: ' . $e->getMessage();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
    }

    protected function createDescription($product)
    {
        $name = $product['description'] ?: '';
        $description = $product['additionalDescription'] ?: '';
        $manufacturerArticleNumber = $product['manufacturerArticleNumber'] ?: '';
        $idSolR = $product['idSolr'] ?: '';
        $euDirectiveNumber = $product['tyreLabel']['euDirectiveNumber'] ?: '';
        $vehicleClass = $product['tyreLabel']['vehicleClass'] ?: '';

        $description = "
            <h2>Informazioni prodotto</h2>
            <h3>Classe veicolo: {$vehicleClass}</h3>
            <p>Pneumatico: {$name}</p>
            <p>Descrizione: {$description}</p>
            <p>Numero articolo: {$manufacturerArticleNumber}</p>
            <p>Id SolR: {$idSolR}</p>
            <p>Numero direttiva UE: {$euDirectiveNumber}</p>
        ";

        return $this->createMultiLangField($description);
    }

    protected function getOrCreateManufacturer($manufacturerArray)
    {
        $languages = \Language::getLanguages();
        $id_manufacturer = (int) \Manufacturer::getIdByName($manufacturerArray['name']);

        // Se esiste già, restituisco l'ID
        if ($id_manufacturer > 0) {
            return $id_manufacturer;
        }

        $manufacturer = new \Manufacturer();

        $manufacturer->name = strtoupper($manufacturerArray['name']);
        $manufacturer->active = 1;

        foreach ($languages as $language) {
            $manufacturer->description[$language['id_lang']] = $manufacturerArray['description'];
        }

        $result = $manufacturer->save();

        if ($result) {
            // Scarico l'immagine
            $id_manufacturer = $manufacturer->id;

            // Creo la directory se non esiste
            $dir = _PS_MANU_IMG_DIR_;
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            // Scarico l'immagine dal web
            $image_url = $manufacturerArray['image'];
            $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
            $image_downloaded = false;

            try {
                if (@file_put_contents($temp_name, file_get_contents($image_url))) {
                    $image_downloaded = true;
                }
            } catch (\Throwable $th) {
                $this->errors[] = "Errore {$th->getMessage()} durante il salvataggio dell'immagine: {$image_url}";
                $image_downloaded = false;
            }

            if ($image_downloaded) {
                // Genero il nome dell'immagine
                $image_name = $id_manufacturer . '.jpg';

                // Salvo l'immagine nella directory dei manufacturer
                if (\ImageManager::resize($temp_name, _PS_MANU_IMG_DIR_ . $image_name)) {
                    // Genero i thumbnails
                    $images_types = \ImageType::getImagesTypes('manufacturers');
                    foreach ($images_types as $image_type) {
                        \ImageManager::resize(
                            $temp_name,
                            _PS_MANU_IMG_DIR_ . $image_name . '-' . $image_type['name'] . '.jpg',
                            $image_type['width'],
                            $image_type['height']
                        );
                    }
                }

                // Elimino il file temporaneo
                @unlink($temp_name);
            }

            return $id_manufacturer;
        }

        return false;
    }

    /**
     * Cerca o crea una caratteristica
     * 
     * @param string $name Nome della caratteristica
     * @return int ID della caratteristica
     */
    protected function getOrCreateFeature($name)
    {
        // Cerco la caratteristica per nome
        $id_feature = \Db::getInstance()->getValue(
            'SELECT f.id_feature
            FROM `' . _DB_PREFIX_ . 'feature` f
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
            ON (f.id_feature = fl.id_feature AND fl.id_lang = ' . (int) $this->default_lang_id . ')
            WHERE fl.name = "' . pSQL($name) . '"'
        );

        if (!$id_feature) {
            // Creo la caratteristica
            $feature = new \Feature();
            $feature->name = $this->createMultiLangField($name);
            $feature->add();
            $id_feature = $feature->id;
        }

        return $id_feature;
    }

    /**
     * Cerca o crea un valore per una caratteristica
     * 
     * @param int $id_feature ID della caratteristica
     * @param string $value Valore della caratteristica
     * @return int ID del valore della caratteristica
     */
    protected function getOrCreateFeatureValue($id_feature, $value)
    {
        $id_feature_value = \Db::getInstance()->getValue(
            'SELECT fv.id_feature_value
            FROM `' . _DB_PREFIX_ . 'feature_value` fv
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
            ON (fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int) $this->default_lang_id . ')
            WHERE fv.id_feature = ' . (int) $id_feature . ' AND fvl.value = "' . pSQL($value) . '"'
        );

        if (!$id_feature_value) {
            // Creo il valore della caratteristica
            $feature_value = new \FeatureValue();
            $feature_value->id_feature = $id_feature;
            $feature_value->value = $this->createMultiLangField($value);
            $feature_value->add();
            $id_feature_value = $feature_value->id;
        }

        return $id_feature_value;
    }
}