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
        $deactivated = $db->Affected_Rows();
        echo "\n\t Disattivati {$deactivated} prodotti nella tabella Tyre";
        $db->execute("UPDATE {$pfx}product SET active = '0' WHERE reference NOT LIKE '00-%' OR reference NOT LIKE 'PFU%'");
        $deactivated = $db->Affected_Rows();
        echo "\n\t Disattivati {$deactivated} prodotti nella tabella Product";
        $db->execute("UPDATE {$pfx}product_shop SET active = '0' WHERE id_product in (SELECT id_product from {$pfx}product where active=0);");
        $deactivated = $db->Affected_Rows();
        echo "\n\t Disattivati {$deactivated} prodotti nella tabella Product Shop";

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
            $row['load_amount'] = $loadAmount;
            $row['load_perc'] = $loadPerc;

            $result = self::insertRow($row);
            if ($result) {
                $total++;
            }
        }

        return $total;
    }

    private static function insertRow($tyre)
    {
        $result = false;

        try {
            if (!$tyre['matchcode']) {
                echo "\nProdotto tyre senza matchcode ({$tyre['idT24']}). Saltato";
                return false;
            }

            // Controllo che ci sia il riferimento del record CSV e API
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
                    AND
                    active=1

                UNION
                
                SELECT
                    *
                FROM
                    {$pfx}product_tyre
                WHERE
                    id_t24 = {$tyre['idT24']}
                    AND
                    type = 'API'
            ";
            $rows = $db->executeS($query);

            if (!$rows) {
                echo "\nProdotto id {$tyre['idT24']} non trovato nei record CSV";
                return false;
            }

            $row_csv = null;
            $row_api = null;

            if (count($rows) == 1 && !$rows[0]['type'] == 'CSV') {
                echo "\nCorrispondenza CSV per id {$tyre['idT24']} API non trovata";
                return false;
            }

            if ($rows[0]['type'] == 'CSV') {
                $row_csv = $rows[0];
            }

            if (isset($rows[1]) && $rows[1]['type'] == 'API') {
                $row_api = $rows[1];
            }

            $json_csv = json_decode($row_csv['content'], true);
            $tyre['csv_image_url'] = $json_csv['image'];
            $tyre['csv_image_url'] = preg_replace('/w\d\d\d/', 'w800', $tyre['csv_image_url']);
            $tyre['csv_image_url'] = preg_replace('/h\d\d\d/', 'h800', $tyre['csv_image_url']);
            $tyre['csv_label_url'] = $json_csv['tyrelabel_link'];
            $tyre['csv_label_url'] = preg_replace('/w\d\d\d/', 'w800', $tyre['csv_label_url']);
            $tyre['csv_label_url'] = preg_replace('/h\d\d\d/', 'h800', $tyre['csv_label_url']);
            $tyre['availability'] = $json_csv['availability'] ?? 0;
            $tyre['delivery_date'] = $json_csv['expected_delivery_date'] ?? '';
            $tyre['height'] = $json_csv['height'] ?? 0;
            $tyre['width'] = $json_csv['width'] ?? 0;
            $tyre['depth'] = $json_csv['inner_diameter'] ?? 0;
            $tyre['tyre_type'] = $json_csv['tyre_type'] ?? 0;
            $tyre['usage'] = $json_csv['usage'] ?? '';
            $tyre['ms'] = $json_csv['ms'] ?? '';
            $tyre['price_unit'] = (float) $json_csv['price_1'];
            $tyre['price_set'] = (float) $json_csv['price_4'];
            $tyre['price_unit_loaded'] = self::addPriceLoad($json_csv['price_1'], $tyre['load_amount'], $tyre['load_perc']);
            $tyre['price_set_loaded'] = self::addPriceLoad($json_csv['price_4'], $tyre['load_amount'], $tyre['load_perc']);

            // Dati da inserire nel record API
            $values = [
                'id_t24' => $tyre['idT24'],
                'type' => 'API',
                'matchcode' => $tyre['matchcode'],
                'content' => pSQL(json_encode($tyre)),
                'price_unit' => (float) $tyre['price_unit'],
                'price_set' => (float) $tyre['price_set'],
                'load_amount' => (float) $tyre['load_amount'],
                'load_perc' => (float) $tyre['load_perc'],
                'price_unit_loaded' => (float) $tyre['price_unit_loaded'],
                'price_set_loaded' => (float) $tyre['price_set_loaded'],
                'active' => 1,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => null,
            ];

            if ($tyre['availability'] == 0) {
                return false;
            }

            if ($tyre['delivery_date'] == '') {
                return false;
            }

            if ($tyre['price_unit_loaded'] == 0) {
                return false;
            }

            $db = \Db::getInstance();

            if ($row_api) {
                $db->delete('product_tyre', "id_t24 = {$tyre['idT24']} AND type = 'API'");
                echo PHP_EOL . 'Cancellati ' . \Db::getInstance()->Affected_Rows() . ' record API per il prodotto ' . $tyre['idT24'] . ' ' . $tyre['matchcode'] . '.';
            }

            try {
                $result = $db->insert(
                    'product_tyre',
                    $values,
                    true,
                    false,
                    \DbCore::INSERT,
                    true
                );
            } catch (\Throwable $th) {
                echo "\n\t Errore {$th->getMessage()} ({$th->getLine()}) {$th->getFile()} durante il salvataggio del prodotto {$tyre['idT24']} {$tyre['matchcode']}";
                $result = false;
            }
        } catch (\Throwable $th) {
            echo "\n\t Errore {$th->getMessage()} ({$th->getLine()}) {$th->getFile()} durante il salvataggio del prodotto {$tyre['idT24']} {$tyre['matchcode']}";
            $result = false;
        }

        return $result;
    }

    public static function addPriceLoad($price, $loadAmount = 0, $loadPerc = 0)
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

        // Fase 1 Disattivo tutti i prodotti prestashop tyre non presenti nella tabella product_tyre
        $db = \Db::getInstance();
        $rows = self::getTyresRows();

        $totalRows = count($rows);
        $parsed = 0;
        echo "\n\t Procedo al parsing di {$totalRows} prodotti scaricati da Tyre.";

        // Leggo il codice dell'IVA da applicare
        $id_tax_rules_group = (int) \Configuration::get('MPAPITYRES_ID_TAX_RULES_GROUP');
        $loadAmount = (float) \Configuration::get('MPAPITYRES_RELOAD_AMOUNT');
        $loadPerc = (float) \Configuration::get('MPAPITYRES_RELOAD_PERCENTAGE');
        $id_category_default = (int) GetCategoryIdByName::get(\Configuration::get('MPAPITYRES_DEFAULT_CATEGORY'));
        $delay = (int) \Configuration::get('MPAPITYRES_DELIVERY_DELAY');

        foreach ($rows as $row) {
            // Decodifico il prodotto dal valore JSON della tabella
            $tyre = json_decode($row['content'], true);

            $tyre['id_tax_rules_group'] = $id_tax_rules_group;
            $tyre['id_category_default'] = $id_category_default;
            $tyre['delay'] = $delay;
            $tyre['load_amount'] = (float) $loadAmount;
            $tyre['load_perc'] = (float) $loadPerc;
            $tyre['price_unit_loaded'] = (float) $row['price_unit_loaded'];
            $tyre['price_set_loaded'] = (float) $row['price_set_loaded'];

            // Chiamo la funzione che importa il prodotto nel catalogo Prestashop
            $response = self::updateProduct($tyre);
            if ($response) {
                $parsed++;
            }
        }

        self::deactivateProductsByDate();

        echo "\n\t Importazione completata. Inseriti {$parsed} prodotti su un totale di {$totalRows}.";
    }

    public static function deactivateProductsByDate()
    {
        $pfx = _DB_PREFIX_;
        \Db::getInstance()->execute("UPDATE `{$pfx}product` SET active = 0 WHERE id_product IN (SELECT distinct id_product from {$pfx}product_lang where DATE(delivery_in_stock) IS NOT NULL AND DATE(delivery_in_stock) <= DATE_FORMAT(NOW(), '%Y-%m-%d'));");
        $deactivated = \Db::getInstance()->Affected_Rows();
        echo "\n\t Disattivati {$deactivated} prodotti nel catalogo del negozio con data non valida.";
        \Db::getInstance()->execute("UPDATE `{$pfx}product_shop` SET active = 0 WHERE id_product IN (SELECT distinct id_product from {$pfx}product_lang where DATE(delivery_in_stock) IS NOT NULL AND DATE(delivery_in_stock) <= DATE_FORMAT(NOW(), '%Y-%m-%d'));");
        $deactivated = \Db::getInstance()->Affected_Rows();

        \Db::getInstance()->execute("
            UPDATE `{$pfx}product` SET active = 1 WHERE id_product IN
            (
                SELECT
                    distinct a.id_product
                FROM {$pfx}product a
                INNER JOIN {$pfx}stock_available b ON (a.id_product = b.id_product AND b.id_product_attribute=0)
                WHERE
                    (a.reference LIKE '00-%' OR a.reference LIKE 'PFU%')
                    AND
                    b.quantity > 0
            );");
        $activated = \Db::getInstance()->Affected_Rows();
        echo "\n\t Attivati {$activated} prodotti non appartenenti ai prodotti Tyre.";

        \Db::getInstance()->execute("
            UPDATE `{$pfx}product_shop` SET active = 1 WHERE id_product IN
            (
                SELECT
                    distinct a.id_product
                FROM {$pfx}product a
                INNER JOIN {$pfx}stock_available b ON (a.id_product = b.id_product AND b.id_product_attribute=0)
                WHERE
                    (a.reference LIKE '00-%' OR a.reference LIKE 'PFU%')
                    AND
                    b.quantity > 0
            );");
        $activated = \Db::getInstance()->Affected_Rows();
        echo "\n\t Attivati {$activated} prodotti del negozio non appartenenti ai prodotti Tyre.";
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

        $data = [
            'filter-0' => json_decode(\Configuration::get('MPAPITYRES_FILTER_0'), true),
            'filter-1' => json_decode(\Configuration::get('MPAPITYRES_FILTER_1'), true),
            'filter-2' => json_decode(\Configuration::get('MPAPITYRES_FILTER_2'), true),
            'filter-4' => json_decode(\Configuration::get('MPAPITYRES_FILTER_4'), true),
            'filter-5' => json_decode(\Configuration::get('MPAPITYRES_FILTER_5'), true),
            'filter-6' => json_decode(\Configuration::get('MPAPITYRES_FILTER_6'), true),
        ];

        if ($data) {
            $dataFilter = '';
            foreach ($data as $key => $filter) {
                if (!is_array($filter)) {
                    $filter = [$filter];
                }
                if (in_array('all', $filter)) {
                    continue;
                }
                switch ($key) {
                    case 'filter-0':
                        $name = 'tyretype';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                    case 'filter-1':
                        $name = 'manufacturer';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                    case 'filter-2':
                        $name = 'manufacturer_category';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                    case 'filter-4':
                        $name = 'speed';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                    case 'filter-5':
                        $name = 'property';
                        $multipleChoice = 1;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                    case 'filter-6':
                        $name = 'express_delivery';
                        $multipleChoice = 0;
                        $dataFilter .= "filter[{$key}][name]={$name}&";
                        break;
                }
                foreach ($filter as $filterValue) {
                    $dataFilter .= "filter[{$key}][valueList][]={$filterValue}&";
                }
                $dataFilter .= "filter[{$key}][multipleChoice]={$multipleChoice}&";
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
        if (!$product['matchcode']) {
            echo "\n\t Non ho il matchcode per il prodotto {$product['idT24']}, importazione saltata.";
            return false;
        }

        try {
            $reference = $product['matchcode'];
            $id_product = (int) $product['idT24'];

            $db = \Db::getInstance();

            $productObj = new \Product($id_product);
            if (\Validate::isLoadedObject($productObj)) {
                if ($productObj->price != $product['price_unit_loaded']) {
                    echo "\n\t Prezzo prodotto {$id_product} aggiornato da {$productObj->price} a {$product['price_unit_loaded']}";
                    $db->update('product', ['price' => $product['price_unit_loaded']], 'id_product = ' . $id_product);
                    $db->update('product_shop', ['price' => $product['price_unit_loaded']], 'id_product = ' . $id_product);
                }

                // Aggiorno il prezzo del set (4 prodotti)
                if ($product['price_set_loaded'] && $product['price_unit_loaded'] != $product['price_set_loaded']) {
                    $price_4 = (float) $product['price_set_loaded'];
                    self::addQuantityPrice($id_product, 4, $price_4);
                } elseif ($product['price_set_loaded'] && $product['price_unit_loaded'] == $product['price_set_loaded']) {
                    self::removeQuantityPrice($id_product, 4);
                }

                if (\StockAvailable::getQuantityAvailableByProduct($id_product, 0) != $product['availability']) {
                    echo "\n\t Quantità prodotto {$id_product} aggiornata da {$productObj->quantity} a {$product['availability']}";
                    \StockAvailable::setQuantity($id_product, 0, (int) $product['availability']);
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
                $delay = self::getDeliveryDateDelay($deliveryDate, $product['delay']);
                if ($delay) {
                    self::updateDeliveryDate($id_product, $delay);
                } else {
                    $delay = self::getDeliveryDateDelay(date('Y-m-d'), 7);
                    self::updateDeliveryDate($id_product, $delay);
                }

                return true;
            }

            // Controllo che il prezzo non sia Zero
            if ($product['price_unit_loaded'] == 0) {
                echo "\n\t Prezzo prodotto {$id_product} non valido";
                return false;
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
            $prestashopProduct->price = (float) $product['price_unit_loaded'];
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
            $prestashopProduct->id_category_default = $product['id_category_default'];

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
            $categories = [$product['id_category_default']];
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
            if ($product['price_set_loaded'] > 0 && $product['price_unit_loaded'] != $product['price_set_loaded']) {
                self::addQuantityPrice($id_product, 4, (float) $product['price_set_loaded']);
            }

            // Aggiorno la scadenza
            $deliveryDate = $product['delivery_date'] ?? '';
            $delay = self::getDeliveryDateDelay($deliveryDate, $product['delay']);
            if ($delay) {
                self::updateDeliveryDate($id_product, $delay);
            } else {
                $delay = self::getDeliveryDateDelay(date('Y-m-d'), 7);
                self::updateDeliveryDate($id_product, $delay);
            }

            // Aggiorno il PFU
            $createPfu = new CreatePFU();
            $createPfu->setProductToPfu($id_product);

            echo "\n\t È stato aggiunto un nuovo prodotto: {$id_product} EAN: {$product['ean']}";

            return true;
        } catch (\Exception $e) {
            echo "\n\t Errore durante l'aggiornamento del prodotto: " . $e->getMessage();

            return false;
        }
    }

    public static function getDeliveryDateDelay($delivery_date, $delay = 0)
    {
        if (\Validate::isDate($delivery_date)) {
            if (!$delay) {
                $delay = (int) (\Configuration::get('MPAPITYRES_DELIVERY_DELAY') ?: 2);
            }

            if (!$delay) {
                return date('Y-m-d', strtotime($delivery_date));
            }

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

    public static function removeDeliveryDate($id_product)
    {
        $db = \Db::getInstance();
        return (int) $db->update(
            'product_lang',
            [
                'delivery_in_stock' => null,
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
            echo "\n\t {$id_product}: Il prezzo specifico {$price} per {$quantity} pneumatici è stato creato.";
        }

        return $result;
    }

    /**
     * Rimuove il prezzo specifico per quantit�
     * @param int $id_product ID del prodotto
     * @param int $quantity Quantit�
     * @return void
     */
    public static function removeQuantityPrice($id_product, $quantity)
    {
        $db = \Db::getInstance();
        $id_product = (int) $id_product;
        $quantity = (int) $quantity;

        // Verifica se il prodotto esiste
        if (!\Product::existsInDatabase($id_product, 'product')) {
            echo "\n\t Prodotto {$id_product} non trovato.";
        }

        // Verifica se esiste già uno specific price per quantità >= 4
        $pfx = _DB_PREFIX_;
        $existingPrice = $db->getValue("SELECT id_specific_price FROM {$pfx}specific_price WHERE id_product = {$id_product} AND from_quantity >= {$quantity} AND id_shop = 0 AND id_currency = 0 AND id_country = 0 AND id_group = 0");

        if ($existingPrice) {
            $specificPrice = new \SpecificPrice($existingPrice);
            if (\Validate::isLoadedObject($specificPrice)) {
                $specificPrice->delete();
            }

            echo "\n\t {$id_product}: Il prezzo specifico per {$quantity} pneumatici è stato eliminato perchè uguale al prezzo singolo.";
        }
    }

    public static function disablePriceZero()
    {
        $pfx = _DB_PREFIX_;
        \Db::getInstance()->execute("UPDATE `{$pfx}product` SET active = 0 WHERE price = 0 AND active = 1;");
        echo PHP_EOL . 'Disabilitati ' . \Db::getInstance()->Affected_Rows() . ' prodotti con prezzo 0.' . PHP_EOL;
        \Db::getInstance()->execute("UPDATE `{$pfx}product_shop` SET active = 0 WHERE id_product IN (SELECT id_product FROM `{$pfx}product` WHERE active = 0);");
        echo PHP_EOL . 'Disabilitati ' . \Db::getInstance()->Affected_Rows() . ' prodotti del negozio con prezzo 0.' . PHP_EOL;
    }

    public static function activateProductsAPI()
    {
        $pfx = _DB_PREFIX_;
        $db = \Db::getInstance();

        $QUERY_P = "UPDATE `{$pfx}product` set active=1 WHERE id_product IN (SELECT id_t24 FROM {$pfx}product_tyre WHERE type='API' AND active=1 AND price_unit_loaded>0);";
        $QUERY_PS = "UPDATE `{$pfx}product_shop` set active=1 WHERE id_product IN (SELECT id_t24 FROM {$pfx}product_tyre WHERE type='API' AND active=1 AND price_unit_loaded>0);";

        $db->execute($QUERY_P);
        echo PHP_EOL . 'Attivati ' . \Db::getInstance()->Affected_Rows() . ' prodotti importati da tyre.';
        $db->execute($QUERY_PS);
        echo PHP_EOL . 'Attivati ' . \Db::getInstance()->Affected_Rows() . ' prodotti del negozio importati da tyre.';
    }

    public static function addPFU()
    {
        $pfx = _DB_PREFIX_;
        $db = \Db::getInstance();

        $query = "
            SELECT
                id_product
            FROM {$pfx}product
            WHERE
                active = 1
                AND 
                reference NOT LIKE 'PFU-%'
                AND id_product NOT IN 
                    (SELECT distinct id_product FROM {$pfx}product_pfu)
            ORDER BY id_product ASC
        ";

        $result = $db->executeS($query);
        $pfu = new CreatePFU();

        foreach ($result as $row) {
            $id_product = $row['id_product'];
            $pfu->setProductToPfu($id_product);
            echo "\n\t{$id_product}: PFU creato.";
        }

        echo "\n*** OPERAZIONE ESEGUITA ***\n";
    }
}
