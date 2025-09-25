<?php

namespace MpSoft\MpApiTyres\Product;

use \Category;
use \Combination;
use \Feature;
use \FeatureValue;
use \PrestaShopException;
use \Product;
use \StockAvailable;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Domain\Product\Attribute\Command\AddAttributeGroupCommand;
use PrestaShop\PrestaShop\Core\Domain\Product\Attribute\CommandHandler\AddAttributeGroupHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Product\Attribute\Value\Command\AddAttributeCommand;
use PrestaShop\PrestaShop\Core\Image\ImageFormatConfiguration;

/**
 * Classe helper per creare un prodotto PrestaShop 8 da un file JSON.
 *
 * Esempio struttura JSON attesa:
 * {
 *   "name": "Nome prodotto",
 *   "reference": "SKU123",
 *   "ean13": "1234567890123",
 *   "brand": "Marca",
 *   "supplier_default": "Fornitore2",
 *   "suppliers": ["Fornitore1", "Fornitore2", "Fornitore3"],
 *   "suppliers_data": {
 *     "Fornitore1": {"reference": "SKU-F1", "price": 80},
 *     "Fornitore2": {"reference": "SKU-F2", "price": 78},
 *     "Fornitore3": {"reference": "SKU-F3", "price": 82}
 *   },
 *   "description": "Descrizione prodotto",
 *   "description_short": "Descrizione breve prodotto",
 *   "price": 99.99,
 *   "quantity": 10,
 *   "default_category": "Pneumatici",
 *   "categories": ["Pneumatici", "Auto"],
 *   "images": ["/path/to/img1.jpg", "https://.../img2.jpg"],
 *   "attributes": {
 *     "Colore": ["Rosso", "Blu"],
 *     "Misura": ["195/65R15"]
 *   },
 *   "features": {
 *     "Stagione": "Estivo",
 *     "Runflat": "No"
 *   },
 *   "combinations_data": [
 *     {
 *       "attributes": {"Colore": "Rosso", "Misura": "195/65R15"},
 *       "reference": "SKU123-R-15",
 *       "ean13": "1111111111111",
 *       "price": 88.50,
 *       "quantity": 5,
 *       "suppliers": ["Fornitore1", "Fornitore2"],
 *       "suppliers_data": {
 *         "Fornitore1": {"reference": "SKU123-R-15-F1", "price": 80},
 *         "Fornitore2": {"reference": "SKU123-R-15-F2", "price": 78}
 *       }
 *     }
 *     // ...altre combinazioni
 *   ]
 * }
 */
class ProductJson
{
    /**
     * Crea un prodotto PrestaShop da un array o stringa JSON
     * @param array|string $json
     * @return array ['success' => true, 'id' => int, 'error' => string]
     */
    public static function createFromJson($json)
    {
        if (is_string($json)) {
            $data = json_decode($json, true);
        } else {
            $data = $json;
        }
        if (!is_array($data)) {
            throw new \Exception('JSON non valido');
        }
        $id_lang = (int) \Context::getContext()->language->id;
        // Validazione base
        if (empty($data['name']) || empty($data['reference']) || empty($data['price'])) {
            throw new \Exception('Dati minimi mancanti (name, reference, price)');
        }
        // Categorie
        $id_categories = [];
        $default_category_id = null;
        if (!empty($data['categories'])) {
            foreach ($data['categories'] as $catName) {
                $cat = self::getOrCreateCategoryByName($catName);
                if ($cat) {
                    $id_categories[] = $cat->id;
                }
            }
        }
        if (!empty($data['default_category'])) {
            $defaultCat = self::getOrCreateCategoryByName($data['default_category']);
            if ($defaultCat) {
                $default_category_id = $defaultCat->id;
                if (!in_array($defaultCat->id, $id_categories)) {
                    $id_categories[] = $defaultCat->id;
                }
            }
        }
        // Brand (Manufacturer)
        $id_manufacturer = null;
        if (!empty($data['brand'])) {
            $id_manufacturer = self::getOrCreateManufacturerByName($data['brand']);
        }
        // Supplier (default) e suppliers associati
        $id_supplier = null;
        $supplier_ids = [];
        if (!empty($data['suppliers']) && is_array($data['suppliers'])) {
            foreach ($data['suppliers'] as $supName) {
                $supId = self::getOrCreateSupplierByName($supName);
                if ($supId) {
                    $supplier_ids[$supName] = $supId;
                }
            }
        }
        if (!empty($data['supplier_default'])) {
            $id_supplier = isset($supplier_ids[$data['supplier_default']])
                ? $supplier_ids[$data['supplier_default']]
                : self::getOrCreateSupplierByName($data['supplier_default']);
        }
        // Prodotto
        $product = new Product();
        $product->name = [
            $id_lang => $data['name']
        ];
        $product->reference = $data['reference'];
        $product->ean13 = $data['ean13'] ?? '';
        $product->price = $data['price'];
        $product->id_category_default = $default_category_id ?: (int) \Configuration::get('PS_HOME_CATEGORY');
        $product->link_rewrite = [
            $id_lang => \Tools::str2url($data['name'])
        ];
        $product->active = 1;
        if (!empty($id_manufacturer)) {
            $product->id_manufacturer = $id_manufacturer;
        }
        if (!empty($id_supplier)) {
            $product->id_supplier = $id_supplier;
        }
        if (!empty($id_categories)) {
            $product->category = $id_categories;
        }
        if (!empty($data['description'])) {
            $product->description = [$id_lang => $data['description']];
        }
        if (!empty($data['description_short'])) {
            $product->description_short = [$id_lang => $data['description_short']];
        }
        try {
            if (!$product->add()) {
                throw new \Exception('Errore creazione prodotto');
            }
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'id' => -1,
                'error' => $th->getMessage(),
            ];
        }
        $product->addToCategories($id_categories);
        // Associa fornitori (gestione tabella product_supplier e default)
        if (!empty($supplier_ids)) {
            $suppliers_data = !empty($data['suppliers_data']) && is_array($data['suppliers_data']) ? $data['suppliers_data'] : [];
            foreach ($supplier_ids as $supName => $id_supplier) {
                // Cerca se già esiste la riga product_supplier
                $exists = (int) \Db::getInstance()->getValue('SELECT id_product_supplier FROM ' . _DB_PREFIX_ . 'product_supplier WHERE id_product = ' . (int) $product->id . ' AND id_supplier = ' . (int) $id_supplier . ' AND id_product_attribute = 0');
                $ref = isset($suppliers_data[$supName]['reference']) ? $suppliers_data[$supName]['reference'] : $product->reference;
                $price = isset($suppliers_data[$supName]['price']) ? $suppliers_data[$supName]['price'] : $product->price;
                if (!$exists) {
                    \Db::getInstance()->insert('product_supplier', [
                        'id_product' => (int) $product->id,
                        'id_product_attribute' => 0,
                        'id_supplier' => (int) $id_supplier,
                        'product_supplier_reference' => pSQL($ref),
                        'product_supplier_price_te' => (float) $price,
                        'id_currency' => (int) \Configuration::get('PS_CURRENCY_DEFAULT')
                    ]);
                }
            }
            // Imposta fornitore di default
            if (!empty($id_supplier)) {
                $product->id_supplier = $id_supplier;
                $product->update();
            }
        }
        // Immagini
        if (!empty($data['images'])) {
            foreach ($data['images'] as $imgPath) {
                self::addProductImage($product->id, $imgPath);
            }
        }
        // Caratteristiche
        if (!empty($data['features'])) {
            foreach ($data['features'] as $featureName => $featureValue) {
                self::addFeatureToProduct($product->id, $featureName, $featureValue);
            }
        }
        // Attributi/Combinazioni
        if (!empty($data['attributes'])) {
            self::addCombinations(
                $product,
                $data['attributes'],
                $data['quantity'] ?? 1,
                $supplier_ids,
                !empty($data['combinations_data']) ? $data['combinations_data'] : []
            );
        } else {
            // Stock semplice
            StockAvailable::setQuantity($product->id, 0, $data['quantity'] ?? 1);
        }
        return [
            'success' => true,
            'id' => $product->id,
            'error' => null
        ];
    }

    private static function getOrCreateCategoryByName($name)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $cats = Category::searchByName($id_lang, $name, 1, true);
        if (!empty($cats)) {
            return new Category((int) $cats[0]['id_category']);
        }
        $cat = new Category();
        $cat->name = [$id_lang => $name];
        $cat->link_rewrite = [$id_lang => \Tools::str2url($name)];
        $cat->id_parent = (int) \Configuration::get('PS_HOME_CATEGORY');
        $cat->active = 1;
        $cat->add();
        return $cat;
    }

    private static function addFeatureToProduct($id_product, $featureName, $featureValue)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $id_feature = self::getFeatureIdByName($featureName, $id_lang);
        if (!$id_feature) {
            $feature = new Feature();
            $feature->name = [$id_lang => $featureName];
            $feature->add();
            $id_feature = $feature->id;
        }
        $id_feature_value = self::getFeatureValueIdByValue($id_feature, $featureValue, $id_lang);
        if (!$id_feature_value) {
            $featureValueObj = new FeatureValue();
            $featureValueObj->id_feature = $id_feature;
            $featureValueObj->value = [$id_lang => $featureValue];
            $featureValueObj->add();
            $id_feature_value = $featureValueObj->id;
        }
        \Db::getInstance()->insert('feature_product', [
            'id_product' => (int) $id_product,
            'id_feature' => $id_feature,
            'id_feature_value' => $id_feature_value
        ], false, true, \DbCore::REPLACE);
    }

    private static function getFeatureIdByName($name, $id_lang)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT f.id_feature FROM ' . _DB_PREFIX_ . 'feature f '
            . 'JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (f.id_feature = fl.id_feature) '
            . 'WHERE fl.name = "' . pSQL($name) . '" AND fl.id_lang = ' . (int) $id_lang
        );
    }

    private static function getFeatureValueIdByValue($id_feature, $value, $id_lang)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT fv.id_feature_value FROM ' . _DB_PREFIX_ . 'feature_value fv '
            . 'JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fv.id_feature_value = fvl.id_feature_value) '
            . 'WHERE fv.id_feature = ' . (int) $id_feature . ' AND fvl.value = "' . pSQL($value) . '" AND fvl.id_lang = ' . (int) $id_lang
        );
    }

    private static function getOrCreateManufacturerByName($name)
    {
        $id = (int) \Db::getInstance()->getValue('SELECT id_manufacturer FROM ' . _DB_PREFIX_ . 'manufacturer WHERE name = "' . pSQL($name) . '"');
        if ($id)
            return $id;
        $man = new \Manufacturer();
        $man->name = $name;
        $man->active = 1;
        $man->add();
        return $man->id;
    }

    private static function getOrCreateSupplierByName($name)
    {
        $id = (int) \Db::getInstance()->getValue('SELECT id_supplier FROM ' . _DB_PREFIX_ . 'supplier WHERE name = "' . pSQL($name) . '"');
        if ($id)
            return $id;
        $sup = new \Supplier();
        $sup->name = $name;
        $sup->active = 1;
        $sup->add();
        return $sup->id;
    }

    private static function addCombinations(Product $product, $attributes, $quantity, $supplier_ids = [], $combinations_data = [])
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $groups = [];
        foreach ($attributes as $groupName => $values) {
            $id_group = self::getAttributeGroupIdByName($groupName, $id_lang);
            if (!$id_group) {
                // Usa il repository Symfony per cercare/creare il gruppo attributo
                $attributeGroupRepo = SymfonyContainer::getInstance()->get('prestashop.core.admin.attribute_group.repository.attribute_group_repository');
                // Prima cerca se esiste (by name)
                $existing = $attributeGroupRepo->findOneByLangAndName($id_lang, $groupName);
                if ($existing && isset($existing['id'])) {
                    $id_group = (int) $existing['id'];
                } else {
                    // Crea nuovo gruppo attributo con CommandBus
                    $commandBus = SymfonyContainer::getInstance()->get('prestashop.core.command_bus');
                    $addAttrGroupCmd = new AddAttributeGroupCommand([
                        $id_lang => $groupName
                    ], [
                        $id_lang => $groupName
                    ], false, 'select');
                    $id_group = $commandBus->handle($addAttrGroupCmd)->getValue();
                }
            }
            $groups[$groupName] = $id_group;
        }
        $comb_keys = array_keys($attributes);
        $combinations = self::cartesian(array_values($attributes));
        foreach ($combinations as $comb) {
            $ids = [];
            $comb_map = [];
            foreach ($comb as $i => $attrValue) {
                $groupName = $comb_keys[$i];
                $id_group = $groups[$groupName];
                $id_attr = self::getAttributeIdByName($attrValue, $id_group, $id_lang);
                if (!$id_attr) {
                    $id_attr = self::createAttributeValue($id_group, $attrValue, $id_lang);
                }
                $ids[] = $id_attr;
                $comb_map[$groupName] = $attrValue;
            }

            // Cerca se esiste combinazione custom in combinations_data
            $comb_data = null;
            foreach ($combinations_data as $cd) {
                if (isset($cd['attributes']) && self::combinationMatch($cd['attributes'], $comb_map)) {
                    $comb_data = $cd;
                    break;
                }
            }
            $ref = $comb_data['reference'] ?? $product->reference;
            $ean = $comb_data['ean13'] ?? $product->ean13;
            $price = $comb_data['price'] ?? $product->price;
            $qty = $comb_data['quantity'] ?? $quantity;
            $comb_suppliers = isset($comb_data['suppliers']) ? $comb_data['suppliers'] : array_keys($supplier_ids);
            $comb_suppliers_data = isset($comb_data['suppliers_data']) ? $comb_data['suppliers_data'] : [];

            // Crea combinazione
            $combination = new Combination();
            $combination->id_product = $product->id;
            $combination->reference = $ref;
            $combination->ean13 = $ean;
            $combination->quantity = $qty;
            $combination->default_on = 1;
            $combination->add();
            $combination->setAttributes($ids);
            StockAvailable::setQuantity($product->id, $combination->id, $qty);

            // Associa fornitori specifici alla combinazione
            foreach ($comb_suppliers as $supName) {
                if (empty($supplier_ids[$supName]))
                    continue;
                $id_supplier = $supplier_ids[$supName];
                $ref_sup = isset($comb_suppliers_data[$supName]['reference']) ? $comb_suppliers_data[$supName]['reference'] : $ref;
                $price_sup = isset($comb_suppliers_data[$supName]['price']) ? $comb_suppliers_data[$supName]['price'] : $price;
                $exists = (int) \Db::getInstance()->getValue('SELECT id_product_supplier FROM ' . _DB_PREFIX_ . 'product_supplier WHERE id_product = ' . (int) $product->id . ' AND id_supplier = ' . (int) $id_supplier . ' AND id_product_attribute = ' . (int) $combination->id);
                if (!$exists) {
                    \Db::getInstance()->insert('product_supplier', [
                        'id_product' => (int) $product->id,
                        'id_product_attribute' => (int) $combination->id,
                        'id_supplier' => (int) $id_supplier,
                        'product_supplier_reference' => pSQL($ref_sup),
                        'product_supplier_price_te' => (float) $price_sup,
                        'id_currency' => (int) \Configuration::get('PS_CURRENCY_DEFAULT')
                    ]);
                }
            }
        }
    }

    /**
     * Verifica se una combinazione corrisponde ai dati JSON (tutti i gruppi devono combaciare)
     */
    private static function combinationMatch($jsonAttrs, $combMap)
    {
        foreach ($jsonAttrs as $k => $v) {
            if (!isset($combMap[$k]) || $combMap[$k] != $v)
                return false;
        }
        return true;
    }

    /**
     * Crea un nuovo valore attributo prodotto (attribute) via SQL diretto
     */
    private static function createAttributeValue($id_group, $value, $id_lang)
    {
        \Db::getInstance()->insert('attribute', [
            'id_attribute_group' => (int) $id_group,
        ]);
        $id_attr = \Db::getInstance()->Insert_ID();
        \Db::getInstance()->insert('attribute_lang', [
            'id_attribute' => (int) $id_attr,
            'id_lang' => (int) $id_lang,
            'name' => pSQL($value),
        ]);
        return $id_attr;
    }

    private static function getAttributeGroupIdByName($name, $id_lang)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT ag.id_attribute_group FROM ' . _DB_PREFIX_ . 'attribute_group ag '
            . 'JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl ON (ag.id_attribute_group = agl.id_attribute_group) '
            . 'WHERE agl.name = "' . pSQL($name) . '" AND agl.id_lang = ' . (int) $id_lang
        );
    }

    private static function getAttributeIdByName($value, $id_group, $id_lang)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT a.id_attribute FROM ' . _DB_PREFIX_ . 'attribute a '
            . 'JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (a.id_attribute = al.id_attribute) '
            . 'WHERE a.id_attribute_group = ' . (int) $id_group . ' AND al.name = "' . pSQL($value) . '" AND al.id_lang = ' . (int) $id_lang
        );
    }

    private static function cartesian($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property_value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public static function addProductImageFromUrl($idProduct, $imgUrl)
    {
        try {
            //code...
            $product = new Product((int) $idProduct);

            if (!\Validate::isLoadedObject($product)) {
                return false;
            }

            $imageInfo = self::getImageInfo($imgUrl);
            $image_uploader = new \HelperImageUploaderCore(basename($imgUrl));

            //Salvo l'immagine nella cartella img/p
            $image_uploader->upload($imageInfo);
            $dest = $image_uploader->getFilePath();

            //file_put_contents($dest, $imageInfo['content']);

            $image = new \Image();
            $image->id_product = (int) ($product->id);
            $image->position = \Image::getHighestPosition($product->id) + 1;

            $image->cover = !\Image::getCover($image->id_product);

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                return $validate;
            }

            if (!$image->add()) {
                return 'Error while creating additional image';
            } else {
                if (!$new_path = $image->getPathForCreation()) {
                    return 'Error while creating additional image';
                } else {
                    if (!copy($dest, $new_path)) {
                        return 'Error while creating additional image';
                    }
                }
            }

            return true;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public static function addProductImage($idProduct, $imgPath)
    {
        $context = \Context::getContext();
        $max_upload = (int) ini_get('upload_max_filesize');
        $max_post = (int) ini_get('post_max_size');
        $max_image_size = min($max_upload, $max_post);

        $product = new Product((int) $idProduct);

        if (!\Validate::isLoadedObject($product)) {
            return false;
        }

        $image_uploader = new \HelperImageUploader($imgPath);
        $imageContent = self::getImageInfo($imgPath);

        if ($imageContent === false) {
            return false;
        }

        $files = [
            [
                'name' => $imageContent['name'],
                'type' => $imageContent['type'],
                'tmp_name' => $imageContent['tmp_name'],
                'size' => $imageContent['size'],
                'content' => base64_decode($imageContent['content']),
                'error' => 0,
            ],
        ];

        foreach ($files as &$file) {
            //Salvo l'immagine nella cartella img/p
            $image_uploader->upload($file);
            $dest = $image_uploader->getFilePath();

            file_put_contents($dest, $file['content']);


            $image = new \Image();
            $image->id_product = (int) ($product->id);
            $image->position = \Image::getHighestPosition($product->id) + 1;

            $image->cover = !\Image::getCover($image->id_product);

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                $file['error'] = $validate;
            }

            if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
                continue;
            }

            if (!$image->add()) {
                $file['error'] = 'Error while creating additional image';
            } else {
                if (!$new_path = $image->getPathForCreation()) {
                    $file['error'] = 'An error occurred while attempting to create a new folder.';

                    continue;
                }

                $error = 0;

                if (!\ImageManager::resize($dest, $new_path . '.' . $image->image_format, null, null, 'jpg', false, $error)) {
                    switch ($error) {
                        case \ImageManagerCore::ERROR_FILE_NOT_EXIST:
                            $file['error'] = 'An error occurred while copying image, the file does not exist anymore.';

                            break;

                        case \ImageManagerCore::ERROR_FILE_WIDTH:
                            $file['error'] = 'An error occurred while copying image, the file width is 0px.';

                            break;

                        case \ImageManagerCore::ERROR_MEMORY_LIMIT:
                            $file['error'] = 'An error occurred while copying image, check your memory limit.';

                            break;

                        default:
                            $file['error'] = 'An error occurred while copying the image.';

                            break;
                    }

                    continue;
                } else {
                    $imagesTypes = \ImageType::getImagesTypes('products');

                    // Should we generate high DPI images?
                    $generate_hight_dpi_images = (bool) \Configuration::get('PS_HIGHT_DPI');

                    /*
                     * Let's resolve which formats we will use for image generation.
                     *
                     * In case of .jpg images, the actual format inside is decided by ImageManager.
                     */
                    $configuredImageFormats = SymfonyContainer::getInstance()->get(ImageFormatConfiguration::class)->getGenerationFormats();

                    foreach ($imagesTypes as $imageType) {
                        foreach ($configuredImageFormats as $imageFormat) {
                            // For JPG images, we let Imagemanager decide what to do and choose between JPG/PNG.
                            // For webp and avif extensions, we want it to follow our command and ignore the original format.
                            $forceFormat = ($imageFormat !== 'jpg');
                            if (
                                !\ImageManager::resize(
                                    $dest,
                                    $new_path . '-' . stripslashes($imageType['name']) . '.' . $imageFormat,
                                    $imageType['width'],
                                    $imageType['height'],
                                    $imageFormat,
                                    $forceFormat
                                )
                            ) {
                                $file['error'] = 'An error occurred while copying this image:' . ' ' . stripslashes($imageType['name']);

                                continue;
                            }

                            if ($generate_hight_dpi_images) {
                                if (
                                    !\ImageManager::resize(
                                        $dest,
                                        $new_path . '-' . stripslashes($imageType['name']) . '2x.' . $imageFormat,
                                        (int) $imageType['width'] * 2,
                                        (int) $imageType['height'] * 2,
                                        $imageFormat,
                                        $forceFormat
                                    )
                                ) {
                                    $file['error'] = 'An error occurred while copying this image:' . ' ' . stripslashes($imageType['name']);

                                    continue;
                                }
                            }
                        }
                    }
                }

                unlink($file['save_path']);
                //Necesary to prevent hacking
                unset($file['save_path']);
                \Hook::exec('actionWatermark', ['id_image' => $image->id, 'id_product' => $product->id]);

                if (!$image->update()) {
                    $file['error'] = 'Error while updating the status.';

                    continue;
                }

                // Associate image to shop from context
                $shops = \Shop::getContextListShopID();
                $image->associateTo($shops);
                $json_shops = [];

                foreach ($shops as $id_shop) {
                    $json_shops[$id_shop] = true;
                }

                $file['status'] = 'ok';
                $file['id'] = $image->id;
                $file['position'] = $image->position;
                $file['cover'] = $image->cover;
                $file['legend'] = $image->legend;
                $file['path'] = $image->getExistingImgPath();
                $file['shops'] = $json_shops;

                @unlink(_PS_TMP_IMG_DIR_ . 'product_' . (int) $product->id . '.jpg');
                @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $product->id . '_' . $context->shop->id . '.jpg');
            }
        }

        return $files;
    }

    public static function getImageContent($imgPath)
    {
        $imageContent = file_get_contents($imgPath);
        $imageType = false;
        if ($imageContent !== false) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imgPath);
            finfo_close($finfo);

            switch ($mimeType) {
                case 'image/jpeg':
                    $imageType = 'jpeg';
                    break;
                case 'image/png':
                    $imageType = 'png';
                    break;
                case 'image/gif':
                    $imageType = 'gif';
                    break;
                case 'image/webp':
                    $imageType = 'webp';
                    break;
                case 'image/avif':
                    $imageType = 'avif';
                    break;
                default:
                    $imageType = 'unknown';
            }
        }

        $allowImages = ['jpeg', 'png', 'jpg', 'webp'];
        if (!in_array($imageType, $allowImages)) {
            return false;
        }

        return [
            'name' => basename($imgPath),
            'type' => $imageType,
            'tmp_name' => $imgPath,
            'content' => base64_encode($imageContent),
            'size' => filesize($imgPath),
            'error' => 0,
        ];
    }

    public static function getImageInfo($imgPath, $getContent = false)
    {
        $isRemote = filter_var($imgPath, FILTER_VALIDATE_URL);

        if ($isRemote) {
            // Scarica il contenuto dell'immagine
            $content = "";
            if ($getContent) {
                $content = @file_get_contents($imgPath);
                if ($content === false) {
                    return false;
                }
            }
            // Ottieni il mime-type dagli header HTTP
            $headers = get_headers($imgPath, 1);
            $mimeType = isset($headers['Content-Type']) ? (is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type']) : null;
            $size = strlen($content);
            $name = basename(parse_url($imgPath, PHP_URL_PATH));
            $path = $imgPath;
        } else {
            // Immagine locale
            if (!file_exists($imgPath)) {
                return false;
            }
            $content = "";
            if ($getContent) {
                $content = @file_get_contents($imgPath);
                if ($content === false) {
                    return false;
                }
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imgPath);
            finfo_close($finfo);
            $size = filesize($imgPath);
            $name = basename($imgPath);
            $path = realpath($imgPath);
        }

        if (!$mimeType || strpos($mimeType, 'image/') !== 0) {
            return false;
        }

        return [
            'name' => $name,
            'type' => $mimeType,
            'tmp_name' => $path,
            'content' => $content,
            'size' => $size,
            'error' => 0,
        ];
    }
}
