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

use MpSoft\MpApiTyres\Helpers\GetLastError;
use MpSoft\MpApiTyres\Helpers\TyreWeight;
use MpSoft\MpApiTyres\Models\ModelProductPfu;

class CreatePFU
{
    private $start;
    private $end;
    private $price;
    private $id_tax_rules_group;
    private $id_start;
    private $error;
    private $pfu;

    public function __construct($start = null, $end = null, $price = null, $idStart = null, $idTaxRulesGroup = null)
    {
        $this->start = $start;
        $this->end = $end;
        $this->price = $price;
        $this->id_start = $idStart;
        $this->id_tax_rules_group = $idTaxRulesGroup;
    }

    public function getError()
    {
        return $this->error;
    }

    public function run()
    {
        $product = new \Product($this->id_start);
        if (\Validate::isLoadedObject($product)) {
            $this->error = "Prodotto {$this->id_start} già esistente";
            return false;
        }

        $product->force_id = true;
        $product->id = $this->id_start;
        $product->id_supplier = 0;
        $product->id_manufacturer = 0;
        $product->id_category_default = \Configuration::get('PS_HOME_CATEGORY');
        $product->id_shop_default = 1;
        $product->id_tax_rules_group = $this->id_tax_rules_group;
        $product->on_sale = 0;
        $product->online_only = 1;
        $product->ean13 = '';
        $product->isbn = '';
        $product->upc = '';
        $product->mpn = '';
        $product->ecotax = 0;
        $product->quantity = 0;
        $product->minimal_quantity = 1;
        $product->low_stock_threshold = 0;
        $product->low_stock_alert = 0;
        $product->price = $this->price;
        $product->wholesale_price = 0;
        $product->unity = '';
        $product->unit_price = 0;
        $product->unit_price_ratio = 0;
        $product->additional_shipping_cost = 0;
        $product->reference = 'PFU-' . $this->start . '-' . $this->end . '-' . $this->price;
        $product->supplier_reference = '';
        $product->location = '';
        $product->width = 0;
        $product->height = 0;
        $product->depth = 0;
        $product->weight = 0;
        $product->out_of_stock = 2;
        $product->additional_delivery_times = 1;
        $product->quantity_discount = 0;
        $product->customizable = 0;
        $product->uploadable_files = 0;
        $product->text_fields = 0;
        $product->active = 1;
        $product->redirect_type = 'default';
        $product->id_type_redirected = 0;
        $product->available_for_order = 1;
        $product->available_date = '0000-00-00';
        $product->show_condition = 0;
        $product->condition = 'new';
        $product->show_price = 1;
        $product->indexed = 0;
        $product->visibility = 'none';
        $product->cache_is_pack = 0;
        $product->cache_has_attachments = 0;
        $product->is_virtual = 1;
        $product->cache_default_attribute = 0;
        $product->advanced_stock_management = 0;
        $product->pack_stock_type = 3;
        $product->state = 1;

        // Descrizione prodotto
        $languages = \Language::getLanguages(false);
        foreach ($languages as $language) {
            $startKg = number_format($this->start / 1000, 2);
            $endKg = number_format($this->end / 1000, 2);
            $id_lang = (int) $language['id_lang'];
            $product->name[$id_lang] = "PFU da {$startKg} Kg a {$endKg} Kg";
            $product->description_short[$id_lang] = "
                <p>L'acronimo <strong>PFU</strong> significa Pneumatici Fuori Uso, ossia gomme da smaltire in seguito al termine del loro ciclo di vita utile.</p>
                <p>Dal <strong>2011</strong> il contributo per lo smaltimento dei PFU si paga sui nuovi pneumatici acquistati e non più al momento dello smontaggio.»/p>
                <p>Al prezzo di vendita dei pneumatici bisogna quindi aggiungere il contributo per lo smaltimento degli stessi.</p>
                <p>Inoltre, tutti coloro i quali intervengono nei passaggi intermedi tra il produttore di pneumatici e il cliente finale, dovranno pagare la stessa cifra al soggetto successivo.</p>
                <p>Il contributo lo riceverà il consorzio che si occupa del ritiro e dello smaltimento dei pneumatici fuori uso che saranno ritirati dai depositi di ciascun gommista.</p>
            ";
            $product->link_rewrite = \Tools::str2url($product->reference);
        }

        // In PrestaShop 8, product_type is a new field
        $product->product_type = 'virtual';

        $add = $product->add();

        if ($add) {
            // Aggiungo l'immagine
            $sourceImgPath = _PS_MODULE_DIR_ . 'mpapityres/views/assets/img/tyre.png';
            $image = new \Image();
            $image->id_product = $product->id;
            $image->cover = 1;
            $image->position = 1;

            $image->add();

            $folders = \Image::getImgFolderStatic($image->id);
            $destImagePath = _PS_PRODUCT_IMG_DIR_ . $folders;

            if (!file_exists($destImagePath)) {
                mkdir($destImagePath, 0775, true);
                chmod($destImagePath, 0775);
            }

            $path = $destImagePath . $image->id . '.' . $image->image_format;

            $put = file_put_contents($path, file_get_contents($sourceImgPath));
            if (!$put) {
                $last_error = GetLastError::get();
                $error = "Codice: {$last_error['code']} - {$last_error['message']} - Riga: {$last_error['line']}";

                $image->delete();

                return $error;
            }

            // Genero i thumbnails
            $images_types = \ImageType::getImagesTypes('products');
            foreach ($images_types as $image_type) {
                \ImageManager::resize(
                    $path,
                    $destImagePath . $image->id . '-' . $image_type['name'] . '.' . $image->image_format,
                    $image_type['width'],
                    $image_type['height']
                );
            }

            return $product->id;
        }

        $last_error = GetLastError::get();
        $error = "Codice: {$last_error['code']} - {$last_error['message']} - Riga: {$last_error['line']}";

        return $error;
    }

    public static function generateRandomString($length = 16)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function setProductToPfu($id_product)
    {
        GetLastError::clear();

        $id_lang = (int) \Context::getContext()->language->id;
        $PFU = $this->getPfu();
        $product = new \Product($id_product, false, $id_lang);
        if (!\Validate::isLoadedObject($product)) {
            return [
                'success' => false,
                'errors' => ["Product {$id_product} not found"],
                'id_pfu' => 0,
            ];
        }

        $width = $product->width;
        $height = $product->height;
        $diameter = $product->depth;
        $tyre = "{$width}/{$height}/{$diameter}";

        $tyreWeight = TyreWeight::calcByCode($tyre);

        $db = \Db::getInstance();
        $db->update(
            'product',
            [
                'weight' => $tyreWeight,
            ],
            'id_product=' . (int) $id_product
        );

        foreach ($PFU as $item) {
            if ($item['weightStart'] / 1000 <= $tyreWeight && $tyreWeight <= $item['weightEnd'] / 1000) {
                $id_pfu = $item['id_product'];

                $productId = (int) $id_product;
                $pfuId = (int) $id_pfu;
                $price = (float) $item['price'];
                $now = date('Y-m-d H:i:s');

                $dbQuery = new \DbQuery();
                $dbQuery
                    ->select('p.id_product')
                    ->from('product', 'p')
                    ->innerJoin('product_pfu', 'pp', 'p.id_product = pp.id_product')
                    ->where("pp.id_product = {$productId}")
                    ->where("p.reference NOT LIKE 'PFU%'");

                $exists = $db->getValue($dbQuery);

                try {
                    if ($exists) {
                        $result = $db->update(
                            'product_pfu',
                            [
                                'id_pfu' => $pfuId,
                                'price' => $price,
                                'active' => 1,
                                'date_upd' => $now,
                            ],
                            "id_product = {$productId}"
                        );
                    } else {
                        $result = $db->insert(
                            'product_pfu',
                            [
                                'id_product' => $productId,
                                'id_pfu' => $pfuId,
                                'price' => $price,
                                'active' => 1,
                                'date_add' => $now,
                                'date_upd' => $now,
                            ],
                            true,
                            false,
                            \DbCore::REPLACE,
                            true
                        );
                    }
                } catch (\Throwable $th) {
                    return [
                        'success' => false,
                        'errors' => "({$th->getCode()}) {$th->getMessage()} - {$th->getLine()}",
                        'id_pfu' => $id_pfu,
                    ];
                }

                $error = GetLastError::get();

                if (!$result) {
                    $id_pfu = 0;
                } else {
                    $id_pfu = $pfuId;
                }

                $errorType = $error['type'] ?? 0;
                $errorCode = $error['code'] ?? 0;
                $errorMessage = $error['message'] ?? 'N/A';
                $errorFile = $error['file'] ?? 'N/A';
                $errorLine = $error['line'] ?? 'N/A';

                return [
                    'success' => $result,
                    'errors' => "({$errorType}) ({$errorCode}) {$errorMessage} - {$errorFile} - {$errorLine}",
                    'id_pfu' => $id_pfu,
                ];
            }
        }

        return [
            'success' => false,
            'errors' => ["Product {$id_product} not found"],
            'id_pfu' => 0,
        ];
    }

    public function getPfu()
    {
        if ($this->pfu) {
            return $this->pfu;
        }

        $this->pfu = ModelProductPfu::getPfuStatic();

        return $this->pfu;
    }
}
