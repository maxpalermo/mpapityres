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

use MpSoft\MpApiTyres\Models\ModelProductTyre;

class PFU
{
    public static function getImage()
    {
        $imagePath = _PS_MODULE_DIR_ . 'mpapityres/views/assets/img/pfu.logo.jpg';
        $content = file_get_contents($imagePath);

        return $content;
    }

    public static function addImageToPfu($id_product, $content)
    {
        $image = new \Image();
        $image->id_product = $id_product;
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

        $put = file_put_contents($path, $content);
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

        return true;
    }

    /**
     * Create a PFU product from the given data
     * @param array $pfu
     * @return bool
     */
    public static function createProductPfu($pfu)
    {
        $id_tax_rules_group = (int) \Configuration::get('MPAPITYRES_PFU_ID_TAX_RULES_GROUP');
        $product = new \Product();

        $product->id_supplier = 0;
        $product->id_manufacturer = 0;
        $product->id_category_default = 2;
        $product->id_shop_default = 1;
        $product->id_tax_rules_group = $id_tax_rules_group;
        $product->on_sale = 0;
        $product->online_only = 1;
        $product->ean13 = '';
        $product->isbn = '';
        $product->upc = '';
        $product->mpn = '';
        $product->ecotax = 0.0;
        $product->quantity = 999999;
        $product->minimal_quantity = 1;
        $product->low_stock_threshold = 0;
        $product->low_stock_alert = 0;
        $product->price = $pfu['price'];
        $product->wholesale_price = 0.0;
        $product->unity = '';
        $product->unit_price = 0.0;
        $product->unit_price_ratio = 1.0;
        $product->additional_shipping_cost = 0.0;
        $product->reference = 'PFU-' . $pfu['min_weight'] . '-' . $pfu['max_weight'];
        $product->supplier_reference = '';
        $product->location = '';
        $product->width = 0.0;
        $product->height = 0.0;
        $product->depth = 0.0;
        $product->weight = 0.0;
        $product->out_of_stock = 1;
        $product->additional_delivery_times = 0;
        $product->quantity_discount = 0;
        $product->customizable = 0;
        $product->uploadable_files = 0;
        $product->text_fields = 0;
        $product->active = 1;
        $product->redirect_type = 'none';
        $product->id_type_redirected = 0;
        $product->available_for_order = 1;
        $product->available_date = date('Y-m-d H:i:s');
        $product->show_condition = 0;
        $product->condition = 'new';
        $product->show_price = 1;
        $product->indexed = 0;
        $product->visibility = 'both';
        $product->cache_is_pack = 0;
        $product->cache_has_attachments = 0;
        $product->is_virtual = 1;
        $product->cache_default_attribute = 0;
        $product->date_add = date('Y-m-d H:i:s');
        $product->date_upd = null;
        $product->advanced_stock_management = 0;
        $product->pack_stock_type = 3;
        $product->state = 1;
        $product->product_type = 'virtual';
        $product->location = '';

        foreach (\Language::getLanguages() as $lang) {
            $product->name[$lang['id_lang']] = "PFU da {$pfu['min_weight']} Kg a {$pfu['max_weight']} Kg";
            $product->description[$lang['id_lang']] = '';
            $product->description_short[$lang['id_lang']] = "
                <p>L'acronimo <strong>PFU</strong> significa Pneumatici Fuori Uso, ossia gomme da smaltire in seguito al termine del loro ciclo di vita utile.</p>
                <p>Dal <strong>2011</strong> il contributo per lo smaltimento dei PFU si paga sui nuovi pneumatici acquistati e non più al momento dello smontaggio.»</p>
                <p>Al prezzo di vendita dei pneumatici bisogna quindi aggiungere il contributo per lo smaltimento degli stessi.</p>
                <p>Inoltre, tutti coloro i quali intervengono nei passaggi intermedi tra il produttore di pneumatici e il cliente finale, dovranno pagare la stessa cifra al soggetto successivo.</p>
                <p>Il contributo lo riceverà il consorzio che si occupa del ritiro e dello smaltimento dei pneumatici fuori uso che saranno ritirati dai depositi di ciascun gommista.</p>
            ";
            $product->link_rewrite[$lang['id_lang']] = "pfu-{$pfu['min_weight']}-{$pfu['max_weight']}";
            $product->available_now = '';
            $product->available_later = '';
            $product->delivery_in_stock = '';
            $product->delivery_out_stock = '';
        }

        $add = $product->add(false, false);
        if ($add) {
            $id_product = (int) $product->id;

            \StockAvailable::setQuantity((int) $id_product, 0, 10000, 0, false);

            $reference = rand(1000, 9999);
            $addPfu = ModelProductTyre::addProduct(
                $id_product,
                $reference,
                'pfu',
                $pfu['min_weight'],
                $pfu['max_weight'],
                $pfu['price'],
                0
            );

            if ($addPfu) {
                // Inserisco l'immagine del pfu
                $image = PFU::getImage();
                $addImage = PFU::addImageToPfu($id_product, $image);

                return $addImage;
            }
        }

        return false;
    }

    public static function getIdPfu($weight)
    {
        $sql = new \DbQuery();
        $sql
            ->select('id_product')
            ->from('product_tyre')
            ->where("type_tyre='pfu'")
            ->where("pfu_weight_min <= {$weight}")
            ->where("pfu_weight_max > {$weight}");

        $id_pfu = (int) \Db::getInstance()->getValue($sql);

        return $id_pfu;
    }

    public static function setPfuToProduct($id_product, $id_pfu, $id_tyre, $weight, $price)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $product = new \Product($id_product, false, $id_lang);
        if (!\Validate::isLoadedObject($product)) {
            return [
                'success' => false,
                'errors' => ["Product {$id_product} not found"],
                'id_pfu' => 0,
            ];
        }

        $db = \Db::getInstance();
        $db->update(
            'product',
            [
                'weight' => $weight,
            ],
            'id_product=' . (int) $id_product
        );

        $sql = new \DbQuery();
        $sql
            ->select('id_product')
            ->from('product_tyre')
            ->where("id_product = {$id_product}");
        $id_product_exists = (int) $db->getValue($sql);

        if ($id_product_exists) {
            $db->update(
                'product_tyre',
                [
                    'id_pfu_associated' => $id_pfu,
                ],
                'id_product=' . (int) $id_product
            );
        } else {
            $db->insert(
                'product_tyre',
                [
                    'id_product' => $id_product,
                    'id_tyre' => $id_tyre,
                    'type_tyre' => 'tyre',
                    'pfu_weight_min' => 0,
                    'pfu_weight_max' => 0,
                    'price' => $price,
                    'id_pfu_associated' => $id_pfu,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => null,
                ],
                true
            );
        }

        return $id_pfu;
    }

    /**
     * Calcola il peso del pneumatico
     * Formula: Volume toroide × peso specifico
     * Volume toroide = π² × D × r²
     * Dove:
     * - r = raggio sezione (metà larghezza in metri)
     * - D = diametro medio del toroide in metri
     *
     * @param int $width Larghezza in mm
     * @param int $aspectRatio Rapporto altezza/larghezza in % (height)
     * @param int $outerDiameter Diametro cerchio in pollici
     * @param int $innerDiameter Diametro interno in pollici
     * @param int $specificWeight Peso specifico del caucciù in Kg/m³
     *
     * @return float Peso in Kg
     */
    public static function getWeightKg($width, $aspectRatio, $innerDiameter, $outerDiameter, $specificWeight = 1350)
    {
        if ($width && $aspectRatio && $innerDiameter) {
            $width_mm = $width;
            $innerDiameter_mm = $innerDiameter * 25.4;
            $sidewallHeight_mm = $aspectRatio * $width_mm / 100;
            $weight = static::_calcWeight($innerDiameter_mm, $sidewallHeight_mm, $width_mm, $specificWeight);
            return $weight;
        }

        if ($width && $outerDiameter && !$innerDiameter) {
            $innerDiameter = $outerDiameter / 2;
        } elseif ($width && $innerDiameter && !$aspectRatio) {
            $aspectRatio = $width / $innerDiameter * 100;
        } elseif ($width && $aspectRatio && $innerDiameter && !$outerDiameter) {
            $outerDiameter = $width / $aspectRatio;
            $innerDiameter = $outerDiameter / 2;
        }

        if ($width && $innerDiameter && $outerDiameter && !$aspectRatio) {
            $width_mm = $width * 25.4;
            $innerDiameter_mm = $innerDiameter * 25.4;
            $aspectRatio_mm = $width_mm / $innerDiameter_mm * 100;
            $sidewallHeight_mm = $aspectRatio_mm * $width_mm / 100;
            $weight = static::_calcWeight($innerDiameter_mm, $sidewallHeight_mm, $width_mm, $specificWeight);

            return $weight;
        }

        if ($width && $innerDiameter && $aspectRatio) {
            $width_mm = $width * 25.4;
            $innerDiameter_mm = $innerDiameter * 25.4;
            $aspectRatio_mm = $aspectRatio * 25.4;
            $sidewallHeight_mm = $aspectRatio_mm / 100;
            $weight = static::_calcWeight($innerDiameter_mm, $sidewallHeight_mm, $width_mm, $specificWeight);
            return $weight;
        }

        return 0;
    }

    private static function _calcWeight($rimDiameter_mm, $sidewallHeight_mm, $width_mm, $specificWeight = 1350)
    {
        // Diametro medio del toroide (cerchio + 2 fianchi) in mm
        $meanDiameter_mm = $rimDiameter_mm + (2 * $sidewallHeight_mm);

        // Raggio della sezione (metà della larghezza del pneumatico) in mm
        $sectionRadius_mm = $width_mm / 2;

        // Converto tutto in metri per il calcolo del volume
        $meanDiameterM = $meanDiameter_mm / 1000;
        $sectionRadiusM = $sectionRadius_mm / 1000;

        // Volume del toroide in m³
        // V = π² × D × r²
        $volume = pow(M_PI, 2) * $meanDiameterM * pow($sectionRadiusM, 2);

        // Peso = Volume × Peso specifico
        $weight = ($volume * $specificWeight) / 4;

        return round($weight, 3);
    }

    /**
     * Stima il peso del SOLO pneumatico (gomma/tele/acciaio), senza aria e senza cerchio,
     * usando una approssimazione geometrica (toroide con sezione ellittica) + densità + fill factor.
     *
     * INPUT:
     * - $widthMm: larghezza (mm) es. 205
     * - $aspectRatio: percentuale spalla (%) es. 55
     * - $innerDiameterIn: diametro cerchio (pollici) es. 16
     * - $class: categoria (motorcycle|car|suv|van|truck|agri|unknown) opzionale
     *
     * OUTPUT:
     * - kg (float)
     */
    public static function estimateTyreWeightKg(
        float $widthMm,
        float $aspectRatio,
        float $innerDiameterIn,
        string $class = 'unknown',
        float $materialDensityKgM3 = 1150.0
    ): float {
        if ($widthMm <= 0) {
            return 0.0;
        }

        if ($widthMm <= 50) {
            $widthMm *= 25.4;
        }

        if ($widthMm && $innerDiameterIn && !$aspectRatio) {
            $aspectRatio = ($widthMm / 25.4) / $innerDiameterIn * 100;
        }

        if ($widthMm <= 0 || $aspectRatio <= 0 || $innerDiameterIn <= 0) {
            return 0.0;
        }

        // Spalla (mm)
        $sidewallMm = $widthMm * ($aspectRatio / 100.0);

        // Diametro cerchio (mm)
        $rimMm = $innerDiameterIn * 25.4;

        // Diametro medio (mm): cerchio + 2 spalle
        $meanDiameterMm = $rimMm + (2.0 * $sidewallMm);

        // Conversioni in metri
        $widthM = $widthMm / 1000.0;
        $sidewallM = $sidewallMm / 1000.0;
        $meanDiameterM = $meanDiameterMm / 1000.0;

        // Volume geometrico (m³): sezione ellittica "spazzata" sulla circonferenza
        // Area ellisse = π * (width/2) * (sidewall/2)
        $crossSectionArea = M_PI * ($widthM / 2.0) * ($sidewallM / 2.0);
        $circumference = M_PI * $meanDiameterM;
        $volumeGeom = $circumference * $crossSectionArea;

        // Fill factor: quota di volume geometrico che è realmente materiale (tarabile)
        $class = strtolower(trim($class));
        $fillRanges = [
            'motorcycle' => [0.16, 0.34],
            'car' => [0.12, 0.3],
            'van' => [0.13, 0.3],
            'suv' => [0.14, 0.3],
            'truck' => [0.16, 0.32],
            'agri' => [0.18, 0.34],
            'unknown' => [0.12, 0.3],
        ];
        [$minFill, $maxFill] = $fillRanges[$class] ?? $fillRanges['unknown'];

        // Stima base (empirica) del fillFactor in funzione di width/aspect
        $fill = 1.1042 - (0.00256 * $widthMm) - (0.00676 * $aspectRatio);

        // Clamp per classe
        if ($fill < $minFill) {
            $fill = $minFill;
        } elseif ($fill > $maxFill) {
            $fill = $maxFill;
        }

        // Peso (kg)
        $weightKg = $volumeGeom * $materialDensityKgM3 * $fill;

        return round($weightKg, 3) * (1.15);
    }
}
