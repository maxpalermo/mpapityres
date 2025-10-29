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

use MpSoft\MpApiTyres\Catalog\CreatePFU;
use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Traits\DownloadImageFromUrlTrait;
use MpSoft\MpApiTyres\Traits\GetCategoryIdFromNameTrait;

class DownloadAPI
{
    use DownloadImageFromUrlTrait;
    use GetCategoryIdFromNameTrait;

    public static function doPostDownload()
    {
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        $db->execute("UPDATE {$pfx}product_tyre SET active = '0' WHERE type='API'");
        $db->execute("UPDATE {$pfx}product SET active = '0' WHERE id_product < 10000000");
        $db->execute("UPDATE {$pfx}product_shop SET active = '0' WHERE id_product < 10000000");

        // Preparo i dati per il download via API
        $configValues = ConfigValues::getInstance();
        $host = $configValues->MPAPITYRES_API_ENDPOINT;
        $token = $configValues->MPAPITYRES_API_TOKEN;
        $endpoint = '/search';
        $offset = 0;
        $limit = 3000;
        $totalRows = 0;

        // Creo un client guzzleHTTP
        $client = new \GuzzleHttp\Client([
            'base_uri' => $host,
            'timeout' => 36000,  // CURLOPT_TIMEOUT
            'verify' => true,  // CURLOPT_SSL_VERIFYPEER
            'allow_redirects' => true,  // CURLOPT_FOLLOWLOCATION
            'http_errors' => false,
        ]);

        // Preparo i parametri per la richiesta
        $filters = self::getFilters();

        $search_params = [
            'search' => '%',
            'offset' => $offset,
            'limit' => $limit,
            'minStock' => 4,
        ];

        $header = [
            'X-AUTH-TOKEN' => $token,
        ];

        $loop = 0;
        // Scarico i dati da Tyre e li inserisco nella tabella product_tyre
        do {
            $loop++;
            echo "\n\t======================";
            echo "\n\t Ciclo n. {$loop}";
            echo "\n\t======================";

            $params = http_build_query($search_params) . ($filters ? '&' . $filters : '');
            // Decodifico il % che viene codificato in %25 da http_build_query
            $params = str_replace('search=%25', 'search=%', $params);

            $result = $client->request('GET', $host . $endpoint . '?' . $params, [
                'headers' => $header,
            ]);

            $body = (string) $result->getBody();
            $rows = json_decode($body, true);
            $countRows = count($rows['tyreList'] ?? []);
            $toOffset = $offset + $limit;
            $totalRows += $countRows;

            echo "\n\t TROVATI: {$countRows} prodotti tyre.";
            echo "\n\t OFFSET DA {$offset} a {$toOffset};";

            $inserted = self::insertRows($rows['tyreList'] ?? []);
            echo "\n\t INSERITI {$inserted} PRODOTTI TYRE";

            $offset = $toOffset;
            $search_params['offset'] = $offset;
        } while ($countRows > 0);

        return "Righe lette: {$totalRows}";
    }

    private static function insertRows($rows)
    {
        $loadAmount = (float) \Configuration::get('MPAPITYRES_RICARICO_PREZZO');
        $loadPerc = (float) \Configuration::get('MPAPITYRES_RICARICO_DEFAULT');
        $total = 0;
        foreach ($rows as $row) {
            $row['loadAmount'] = $loadAmount;
            $row['loadPerc'] = $loadPerc;

            $result = self::insertRow($row);
            if ($result) {
                $total++;
            }
        }

        return $total;
    }

    private static function insertRow($tyre)
    {
        if (!$tyre['matchcode']) {
            echo "\n\t Prodotto tyre senza matchcode ({$tyre['idT24']}). Saltato";
            return false;
        }

        if (!$tyre['ean']) {
            echo "\n\t Prodotto tyre senza EAN13 ({$tyre['idT24']}). Saltato";
            return false;
        }

        // Controllo che ci sia il riferimento del file CSV
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        $query = "
            SELECT
                *
            FROM
                {$pfx}product_tyre
            WHERE
                id_t24 = {$tyre['idT24']}
                AND
                type = 'CSV'
        ";
        $row = $db->getRow($query);

        if (!$row) {
            echo "\n\t id {$tyre['idT24']} non trovato nei record CSV";
            return false;
        }

        $json_csv = json_decode($row['content'], true);
        $tyre['csv_image_url'] = $json_csv['image'];
        $tyre['csv_image_url'] = preg_replace('/w\d\d\d/', 'w800', $tyre['csv_image_url']);
        $tyre['csv_image_url'] = preg_replace('/h\d\d\d/', 'h800', $tyre['csv_image_url']);
        $tyre['csv_label_url'] = $json_csv['tyrelabel_link'];
        $tyre['csv_label_url'] = preg_replace('/w\d\d\d/', 'w800', $tyre['csv_label_url']);
        $tyre['csv_label_url'] = preg_replace('/h\d\d\d/', 'h800', $tyre['csv_label_url']);
        $tyre['price_1'] = (float) ($json_csv['price_1'] ?? 0);
        $tyre['price_4'] = (float) ($json_csv['price_4'] ?? 0);
        $tyre['availability'] = $json_csv['availability'] ?? 0;
        $tyre['delivery_date'] = $json_csv['expected_delivery_date'] ?? '';
        $tyre['height'] = $json_csv['height'] ?? 0;
        $tyre['width'] = $json_csv['width'] ?? 0;
        $tyre['depth'] = $json_csv['inner_diameter'] ?? 0;
        $tyre['tyre_type'] = $json_csv['tyre_type'] ?? 0;
        $tyre['usage'] = $json_csv['usage'] ?? '';
        $tyre['ms'] = $json_csv['ms'] ?? '';

        $values = [
            'id_t24' => $tyre['idT24'],
            'type' => 'API',
            'matchcode' => $tyre['matchcode'],
            'content' => pSQL(json_encode($tyre)),
            'date_add' => date('Y-m-d H:i:s'),
        ];

        if ($tyre['availability'] == 0) {
            return false;
        }

        if ($tyre['delivery_date'] == '') {
            return false;
        }

        if ($tyre['price_1'] == 0) {
            return false;
        }

        $db = \Db::getInstance();
        try {
            $result = $db->insert(
                'product_tyre',
                $values,
                true,
                false,
                \DbCore::REPLACE,
                true
            );
        } catch (\Throwable $th) {
            echo "\n\t Errore {$th->getMessage()} durante il salvataggio del prodotto {$tyre['idT24']} {$tyre['matchcode']}";
            $result = false;
        }

        return $result;
    }

    public static function addpriceLoad($price, $loadAmount = 0, $loadPerc = 0)
    {
        if (!$loadAmount) {
            $loadAmount = (float) \Configuration::get('MPAPITYRES_RICARICO_PREZZO');
        }

        if (!$loadPerc) {
            $loadPerc = (float) \Configuration::get('MPAPITYRES_RICARICO_DEFAULT');
        }

        if ($loadAmount > 0) {
            $price += $loadAmount;
        }

        if ($loadPerc > 0) {
            $price += $price * ($loadPerc / 100);
        }

        return $price;
    }

    public static function parseCatalogTotal()
    {
        // Funzione che si occupa di leggere i dati da product_tyre e inserirli nel catalogo prestashop
        // ATTENZIONE:
        // -- Se il codice EAN non esiste non viene importato
        // -- Se il codice EAN esiste in un prodotto che non fa parte dei prodotti TYRE si deve saltare

        // Fase 1 Disattivo tutti i prodotti prestashop tyre non presenti nella tabella product_tyre
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        $id_lang = (int) \Context::getContext()->language->id;
        $id_categories = \Configuration::get('MPORDERTYRE_CATEGORIES');

        if (!$id_categories) {
            $id_categories = array_column(\Category::getHomeCategories($id_lang, true), 'id_category');
            $id_categories = implode(',', $id_categories);
        }

        $db->execute("
            UPDATE {$pfx}product
            SET active = 0
            WHERE id_category_default IN ({$id_categories})
            AND id_product NOT IN (SELECT id_t24 FROM {$pfx}product_tyre WHERE type='API')
        ");
        $deactivated = $db->Affected_Rows();
        echo "\n\t Disattivati {$deactivated} prodotti nel catalogo";

        $db->execute("
            UPDATE {$pfx}product_shop
            SET active = 0
            WHERE id_category_default IN ({$id_categories})
            AND id_product NOT IN (SELECT id_t24 FROM {$pfx}product_tyre WHERE type='API')
        ");

        $deactivated = (int) $db->Affected_Rows();

        echo "\n\t Disattivati {$deactivated} prodotti nel catalogo del negozio";

        $rows = self::getTyresRows();

        $totalRows = count($rows);
        $parsed = 0;
        echo "\n\t Procedo al parsing di {$totalRows} prodotti scaricati da Tyre.";

        // Leggo il codice dell'IVA da applicare
        $id_tax_rules_group = (int) \Configuration::get('MPAPITYRES_ID_TAX_RULES_GROUP');

        $loadAmount = (float) \Configuration::get('MPAPITYRES_RICARICO_EUR');
        $loadPerc = (float) \Configuration::get('MPAPITYRES_RICARICO_DEFAULT');

        foreach ($rows as $row) {
            // Decodifico il prodotto dal valore JSON della tabella
            $tyre = json_decode($row['content'], true);

            $tyre['loadAmount'] = $loadAmount;
            $tyre['loadPerc'] = $loadPerc;

            // Controllo che il prodotto non esista in altra categoria
            $exists = $db->getValue("
                SELECT
                    id_product
                FROM
                    {$pfx}product
                WHERE
                    ean13 = '{$tyre['ean']}'
                    AND
                    id_category_default NOT IN ({$id_categories})
            ");

            if ($exists) {
                echo "\n\t Il prodotto {$tyre['ean']} esiste come prodotto in magazzino. Saltato.";
                continue;
            }

            $tyre['id_tax_rules_group'] = $id_tax_rules_group;

            // Chiamo la funzione che importa il prodotto nel catalogo Prestashop
            $response = self::updateProduct($tyre);
            if ($response) {
                $parsed++;
            }
        }

        $pfx = _DB_PREFIX_;
        \Db::getInstance()->execute("UPDATE `{$pfx}product` SET active = 0 WHERE id_product IN (SELECT id_t24 from {$pfx}product_tyre where type='CSV' and active=0);");
        \Db::getInstance()->execute("UPDATE `{$pfx}product_shop` SET active = 0 WHERE id_product IN (SELECT id_t24 from {$pfx}product_tyre where type='CSV' and active=0);");
        \Db::getInstance()->execute("UPDATE `{$pfx}product` SET active = 0 WHERE id_product IN (SELECT id_product from {$pfx}product_lang where reference not like '00-%' and delivery_in_stock <= DATE_FORMAT(NOW(), '%Y-%m-%d'));");
        \Db::getInstance()->execute("UPDATE `{$pfx}product_shop` SET active = 0 WHERE id_product IN (SELECT id_product from {$pfx}product_lang where reference not like '00-%' and delivery_in_stock <= DATE_FORMAT(NOW(), '%Y-%m-%d'));");

        echo "\n\t Importazione completata. Inseriti {$parsed} prodotti su un totale di {$totalRows}.";
    }

    public static function getFilters()
    {
        $filename = _PS_MODULE_DIR_ . 'mpapityres' . '/views/assets/json/api_filters.json';
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            try {
                $data = json_decode($content, true);
            } catch (\Throwable $th) {
                $data = [];
            }
        }

        if ($data) {
            $dataFilter = '';
            foreach ($data as $key => $filter) {
                if (!is_array($filter)) {
                    $filter = [$filter];
                }
                $filterKey = str_replace('filter-', '', $key);
                if (in_array('all', $filter)) {
                    continue;
                }
                switch ($key) {
                    case 'filter-0':
                        $name = 'tyretype';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-1':
                        $name = 'manufacturer';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-2':
                        $name = 'manufacturer_category';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-4':
                        $name = 'speed';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-5':
                        $name = 'property';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                    case 'filter-6':
                        $name = 'express_delivery';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$filterKey}][name]={$name}&";
                        break;
                }
                foreach ($filter as $filterValue) {
                    $dataFilter .= "filter[{$filterKey}][valueList][]={$filterValue}&";
                }
                $dataFilter .= "filter[{$filterKey}][multipleChoice]={$multipleChoice}&";
            }
            $dataFilter = trim($dataFilter, '&');
        } else {
            $dataFilter = '';
        }

        return $dataFilter;
    }

    public static function getTyresRows()
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('*')
            ->from('product_tyre')
            ->where("type = 'API'")
            ->orderBy('id_t24 DESC');

        return $db->executeS($query);
    }

    public static function getIdCategoryByName($name)
    {
        $name = pSQL($name);
        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('id_category')
            ->from('category_lang')
            ->where('name = "{$name}"')
            ->where('id_lang = ' . $id_lang);

        return (int) $db->getValue($query) ?: (int) \Configuration::get('PS_DEFAULT_CATEGORY');
    }

    /**
     * Aggiorna o crea un prodotto in PrestaShop
     *
     * @param array $product Dati del prodotto
     * @return bool
     */
    protected static function updateProduct($product)
    {
        $errors = [];
        try {
            $reference = $product['matchcode'] ?? uuid_create(UUID_TYPE_RANDOM);
            $id_product = (int) $product['idT24'];
            $id_category_default = (int) self::getIdCategoryFromName(\Configuration::get('MPAPITYRES_DEFAULT_CATEGORY'));
            if (!$id_category_default) {
                $id_category_default = (int) \Configuration::get('PS_HOME_CATEGORY');
            }

            // Verifica se il prodotto esista già o se l'EAN non sia valido
            $db = \Db::getInstance();
            $pfx = _DB_PREFIX_;
            $ean = $product['ean'] ?? '';
            if (!$ean) {
                echo "\n\t EAN13 non valido o inesistente";
                return false;
            }

            $sql = "SELECT id_product FROM {$pfx}product WHERE ean13 = '{$product['ean']}'";
            $id_product = $db->getValue($sql);
            if ($id_product) {
                $existsProduct = new \Product($id_product);

                self::updateProductPrice($id_product, $product['price_1'], $product['loadAmount'], $product['loadPerc']);

                \StockAvailable::setQuantity($id_product, 0, (int) $product['availability']);
                if ($product['price_4'] && $product['price_1'] != $product['price_4']) {
                    $price_4 = self::addpriceLoad($product['price_4'], $product['loadAmount'], $product['loadPerc']);
                    self::addQuantityPrice($id_product, 4, $price_4);
                }

                // Aggiorno il PFU
                $createPfu = new CreatePFU();
                $createPfu->setProductToPfu($id_product);

                // Controllo che non abbia immagini
                $imageCover = \Image::getCover($id_product);
                if (!$imageCover) {
                    // Gestione delle immagini
                    if (!empty($product['csv_image_url'])) {
                        self::addProductImageStatic($id_product, $product['csv_image_url']);
                    }

                    // Gestione dell'immagine del label
                    if (!empty($product['csv_label_url'])) {
                        self::addProductImageStatic($id_product, $product['csv_label_url']);
                    }
                }

                // Aggiorno la scadenza
                $deliveryDate = $product['delivery_date'] ?? '';
                $delay = self::getDeliveryDateDelay($deliveryDate);
                if ($delay) {
                    $upd = self::updateDeliveryDate($id_product, $delay);
                } else {
                    $upd = 0;
                }

                echo "\n\t Prodotto esistente {$existsProduct->ean13}. Aggiornati prezzi, immagini, PFU e quantità. \n\tData di spedizione ({$upd}) {$delay}";

                return true;
            }

            // Creo o aggiorno il prodotto
            $id_product = (int) $product['idT24'];
            $prestashopProduct = new \Product($id_product);

            // Dati base del prodotto
            $prestashopProduct->force_id = true;
            $prestashopProduct->id = $id_product;
            $prestashopProduct->reference = $reference;
            $prestashopProduct->name = self::createMultiLangField($product['description'] ?? '');
            $prestashopProduct->description_short = self::createDescription($product);
            // $prestashopProduct->description = $this->createMultiLangField($product['description'] ?? '');
            $prestashopProduct->link_rewrite = self::createMultiLangField(
                \Tools::str2url($product['description'] ?? 'product-' . $reference)
            );

            // Prezzi e tasse
            $prestashopProduct->ean13 = (string) ($product['ean'] ?? '');
            $prestashopProduct->price = (float) self::addPriceLoad($product['price_1'] ?? 0, $product['loadAmount'], $product['loadPerc']);
            $prestashopProduct->wholesale_price = (float) ($product['wholesale_price'] ?? 0);
            $prestashopProduct->id_tax_rules_group = (int) ($product['id_tax_rules_group'] ?? 0);

            // Stock
            $prestashopProduct->quantity = (int) ($product['availability'] ?? 0);
            $prestashopProduct->minimal_quantity = (int) ($product['minimal_quantity'] ?? 1);
            $prestashopProduct->low_stock_threshold = (int) ($product['low_stock_threshold'] ?? 0);
            $prestashopProduct->low_stock_alert = (bool) ($product['low_stock_alert'] ?? false);

            // Stato e visibilità
            $prestashopProduct->active = (bool) ($product['active'] ?? true);
            $prestashopProduct->visibility = $product['visibility'] ?? 'both';  // both, catalog, search, none
            $prestashopProduct->available_for_order = (bool) ($product['available_for_order'] ?? true);
            $prestashopProduct->show_price = (bool) ($product['show_price'] ?? true);

            // Misure
            $prestashopProduct->width = (float) ($product['width'] ?? 0);
            $prestashopProduct->height = (float) ($product['height'] ?? 0);
            $prestashopProduct->depth = (float) ($product['depth'] ?? 0);

            // Categorie
            $prestashopProduct->id_category_default = $id_category_default;

            // Creo o Restituisco il produttore
            $manufacturer = [
                'name' => $product['manufacturerName'],
                'description' => $product['manufacturerDescription'],
                'image' => $product['manufacturerImage'],
            ];
            $id_manufacturer = self::getOrCreateManufacturer($manufacturer);
            $prestashopProduct->id_manufacturer = $id_manufacturer;

            // Dimensioni e peso
            $prestashopProduct->width = (float) ($product['width'] ?? 0);
            $prestashopProduct->height = (float) ($product['height'] ?? 0);
            $prestashopProduct->depth = (float) ($product['depth'] ?? 0);
            $prestashopProduct->weight = (float) ($product['weight'] ?? 0);

            // Meta informazioni per SEO
            $deliveryDate = $product['delivery_date'] ?? '';
            $delay = self::getDeliveryDateDelay($deliveryDate);
            if (!$delay) {
                return false;
            }
            $prestashopProduct->delivery_in_stock = $delay;
            $prestashopProduct->meta_title = self::createMultiLangField($product['meta_title'] ?? '');
            $prestashopProduct->meta_description = self::createMultiLangField($product['meta_description'] ?? '');
            $prestashopProduct->meta_keywords = self::createMultiLangField($product['meta_keywords'] ?? '');

            // Salvo il prodotto
            try {
                $result = $prestashopProduct->add();
            } catch (\Throwable $th) {
                echo "\n\t Errore {$th->getMessage()} durante il salvataggio del prodotto: {$reference}";

                return false;
            }

            if (!$result) {
                echo "\n\t Errore durante il salvataggio del prodotto: {$reference}";

                return false;
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
                    'name' => 'Uso',
                    'value' => $product['usage'] ?? null,
                ],
                [
                    'name' => 'M+S',
                    'value' => $product['ms'] ?? null,
                ],
                [
                    'name' => 'Tipo pneumatico',
                    'value' => $product['tyre_type'] ?? null,
                ],
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
                    $id_feature = self::getOrCreateFeature($feature['name']);

                    // Cerco o creo il valore della caratteristica
                    $id_feature_value = self::getOrCreateFeatureValue($id_feature, $feature['value']);

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
            if (isset($product['availability'])) {
                \StockAvailable::setQuantity($id_product, 0, (int) $product['availability']);
            }

            // Imposto lo specific price per 4 pneumatici
            if ($product['price_4'] > 0 && $product['price_1'] != $product['price_4']) {
                self::addQuantityPrice($id_product, 4, $product['price_4']);
            }

            // Aggiorno il PFU
            $createPfu = new CreatePFU();
            $createPfu->setProductToPfu($id_product);

            return true;
        } catch (\Exception $e) {
            echo "\n\t Errore durante l'aggiornamento del prodotto: " . $e->getMessage();

            return false;
        }
    }

    public static function getDeliveryDateDelay($delivery_date)
    {
        if (\Validate::isDate($delivery_date)) {
            $delay = \Configuration::get('MPAPITYRES_DELIVERY_DELAY') ?: 2;
            $date = date('Y-m-d', strtotime($delivery_date . ' +' . $delay . ' days'));

            return $date;
        }

        return date('Y-m-d', strtotime($delivery_date . ' +5 days'));
    }

    public static function updateDeliveryDate($id_product, $delivery_date)
    {
        $db = \Db::getInstance();
        return (int) $db->update(
            'product_lang',
            [
                'delivery_in_stock' => $delivery_date,
            ],
            'id_product=' . (int) $id_product
        );
    }

    public static function updateProductPrice($id_product, $price, $loadAmount = 0, $loadPerc = 0)
    {
        $price = self::addPriceLoad($price, $loadAmount, $loadPerc);

        $db = \Db::getInstance();
        $db->update(
            'product',
            [
                'price' => $price,
                'active' => 1,
            ],
            'id_product = ' . (int) $id_product
        );

        $db->update(
            'product_shop',
            [
                'price' => $price,
                'active' => 1,
            ],
            'id_product = ' . (int) $id_product
        );
    }

    /**
     * Crea un array multilingua per i campi di un prodotto
     *
     * @param string $value Valore da inserire per tutte le lingue
     * @return array Array multilingua
     */
    protected static function createMultiLangField($value)
    {
        $result = [];
        foreach (\Language::getLanguages() as $language) {
            if (is_array($value)) {
                $result[$language['id_lang']] = $value[$language['id_lang']] ?? reset($value);
            } else {
                $result[$language['id_lang']] = $value;
            }
        }
        return $result;
    }

    protected static function createDescription($product)
    {
        $name = $product['description'] ?: '';
        $description = $product['additionalDescription'] ?: '';
        $manufacturerArticleNumber = $product['manufacturerArticleNumber'] ?: '';
        $idSolR = $product['idSolr'] ?: '';
        $euDirectiveNumber = $product['tyreLabel']['euDirectiveNumber'] ?: '';
        $vehicleClass = $product['tyreLabel']['vehicleClass'] ?: '';
        $season = $product['usage'] ?: '';

        $description = "
            <h2>Informazioni prodotto</h2>
            <h3>Classe veicolo: {$vehicleClass}</h3>
            <p>Pneumatico: {$name}</p>
            <p>Descrizione: {$description}</p>
            <p>Numero articolo: {$manufacturerArticleNumber}</p>
            <p>Id SolR: {$idSolR}</p>
            <p>Numero direttiva UE: {$euDirectiveNumber}</p>
            <p>USO: {$season}</p>
        ";

        return self::createMultiLangField($description);
    }

    /**
     * Cerca o crea una caratteristica
     *
     * @param string $name Nome della caratteristica
     * @return int ID della caratteristica
     */
    protected static function getOrCreateFeature($name)
    {
        $default_lang_id = (int) \Configuration::get('PS_LANG_DEFAULT');
        // Cerco la caratteristica per nome
        $id_feature = \Db::getInstance()->getValue(
            'SELECT f.id_feature
            FROM `' . _DB_PREFIX_ . 'feature` f
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
            ON (f.id_feature = fl.id_feature AND fl.id_lang = ' . (int) $default_lang_id . ')
            WHERE fl.name = "' . pSQL($name) . '"'
        );

        if (!$id_feature) {
            // Creo la caratteristica
            $feature = new \Feature();
            $feature->name = self::createMultiLangField($name);
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
    protected static function getOrCreateFeatureValue($id_feature, $value)
    {
        $default_lang_id = (int) \Configuration::get('PS_LANG_DEFAULT');
        $id_feature_value = \Db::getInstance()->getValue(
            'SELECT fv.id_feature_value
            FROM `' . _DB_PREFIX_ . 'feature_value` fv
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
            ON (fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int) $default_lang_id . ')
            WHERE fv.id_feature = ' . (int) $id_feature . ' AND fvl.value = "' . pSQL($value) . '"'
        );

        if (!$id_feature_value) {
            // Creo il valore della caratteristica
            $feature_value = new \FeatureValue();
            $feature_value->id_feature = $id_feature;
            $feature_value->value = self::createMultiLangField($value);
            $feature_value->add();
            $id_feature_value = $feature_value->id;
        }

        return $id_feature_value;
    }

    /**
     * Cerca o crea il produttore
     *
     * @param array $manufacturerArray Dati del produttore
     * @return int ID del valore del produttore
     */
    protected static function getOrCreateManufacturer($manufacturerArray)
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
                echo "\n\t Errore {$th->getMessage()} durante il salvataggio dell'immagine: {$image_url}";
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
     * Aggiunge uno specific price per quantità >= 4
     * @param int $id_product - ID prodotto
     * @param float $reduction - Importo riduzione (es: 0.10 per 10%)
     * @param string $reduction_type - 'percentage' o 'amount'
     * @return bool
     */
    protected static function addQuantityPrice($id_product, $quantity, $price)
    {
        $db = \Db::getInstance();
        $id_product = (int) $id_product;
        $quantity = (int) $quantity;
        $price = (float) $price;

        // Verifica se il prodotto esiste
        if (!\Product::existsInDatabase($id_product, 'product')) {
            echo "\n\t Prodotto {$id_product} non trovato.";
        }

        // Verifica se esiste già uno specific price per quantità >= 4
        $pfx = _DB_PREFIX_;
        $existingPrice = $db->getValue("
            SELECT
                id_specific_price 
            FROM
                {$pfx}specific_price 
            WHERE
                id_product = {$id_product} 
                AND from_quantity >= {$quantity}
                AND id_shop = 0 
                AND id_currency = 0 
                AND id_country = 0 
                AND id_group = 0
        ");

        if ($existingPrice) {
            if ($price == 0) {
                $specificPrice = new \SpecificPrice($existingPrice);
                if (\Validate::isLoadedObject($specificPrice)) {
                    $specificPrice->delete();
                }

                echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato eliminato.";
            } else {
                $price = number_format($price, 6);
                $db->update(
                    'specific_price',
                    [
                        'price' => $price,
                    ],
                    "id_specific_price = {$existingPrice}"
                );

                echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato aggiornato a {$price}.";
            }

            return true;
        }

        if (!$price) {
            echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici non è stato aggiunto perché il prezzo è 0.";
            return false;
        }

        // Crea il nuovo specific price
        $specificPrice = new \SpecificPrice();
        $specificPrice->id_product = $id_product;
        $specificPrice->id_shop = 0;
        $specificPrice->id_currency = 0;
        $specificPrice->id_country = 0;
        $specificPrice->id_group = 0;
        $specificPrice->id_customer = 0;
        $specificPrice->from_quantity = $quantity;
        $specificPrice->price = $price;
        $specificPrice->reduction_type = 'amount';
        $specificPrice->reduction = 0;
        $specificPrice->from = '0000-00-00 00:00:00';
        $specificPrice->to = '0000-00-00 00:00:00';

        $result = $specificPrice->add();

        if ($result) {
            echo "\n\t {$id_product}: Il prezzo specifico {$price} per {$quantity} pneumatici è stato aggiunto.";
        }

        return $result;
    }
}
