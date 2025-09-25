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

class JsonCatalogHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $filenameTyres;
    private $filenameManufacturers;
    private $filenameManufacturersTyres;
    private $filenamePriceList;

    public function __construct()
    {
        parent::__construct();
        $this->filenameTyres = _PS_UPLOAD_DIR_ . '/CSV/API/api_tyres.json';
        $this->filenameManufacturers = _PS_UPLOAD_DIR_ . '/CSV/API/api_manufacturers.json';
        $this->filenameManufacturersTyres = _PS_UPLOAD_DIR_ . '/CSV/API/api_manufacturers_tyres.json';
        $this->filenamePriceList = _PS_UPLOAD_DIR_ . '/CSV/API/api_price_list.json';
    }

    /**
     * Write TyreList in a Json File
     * @param mixed $content
     * @return bool|int
     */
    public function write($tyreList)
    {
        $tyreListJson = $this->readTyres();
        $manufacturersJson = $this->readManufacturers();
        $manufacturersTyresJson = $this->readManufacturersTyres();
        $priceListJson = $this->readPriceList();

        foreach ($tyreList as $key => $item) {
            $idT24 = $item['idT24'];
            $date_add = date('Y-m-d H:i:s');
            $articleNumber = $item['manufacturerArticleNumber'];
            $name = $item['manufacturerName'];
            $description = $item['manufacturerDescription'];
            $image = $item['manufacturerImage'];

            $tyreListJson[$idT24] = $item;
            $manufacturersJson[$name] = [
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'date_add' => $date_add,
            ];
            $manufacturersTyresJson[$idT24] = [
                'idT24' => $idT24,
                'manufacturerName' => $name,
                'manufacturerArticleNumber' => $articleNumber,
                'date_add' => $date_add,
            ];
            foreach ($item['priceList'] as $idPrice => $price) {
                $price['idT24'] = $idT24;
                $price['date_add'] = $date_add;
                if (strtoupper($price['country_code']) != 'IT') {
                    $priceListJson[$idT24][] = $price;
                    $tyreListJson[$idT24]['priceList'][$idPrice] = $price;
                } else {
                    unset($tyreListJson[$idT24]['priceList'][$idPrice]);
                }
            }
        }

        return $this->writeFiles($tyreListJson, $manufacturersJson, $manufacturersTyresJson, $priceListJson);
    }

    private function writeFiles($tyreListJson, $manufacturersJson, $manufacturersTyresJson, $priceListJson)
    {
        return
            file_put_contents($this->filenameTyres, json_encode($tyreListJson)) &&
            file_put_contents($this->filenameManufacturers, json_encode($manufacturersJson)) &&
            file_put_contents($this->filenameManufacturersTyres, json_encode($manufacturersTyresJson)) &&
            file_put_contents($this->filenamePriceList, json_encode($priceListJson));
    }

    private function readTyres()
    {
        $tyres = [];
        if (file_exists($this->filenameTyres)) {
            $tyres = $this->jsonDecode(file_get_contents($this->filenameTyres));
        }

        return $tyres;
    }

    private function readManufacturers()
    {
        $manufacturers = [];
        if (file_exists($this->filenameManufacturers)) {
            $manufacturers = $this->jsonDecode(file_get_contents($this->filenameManufacturers));
        }

        return $manufacturers;
    }

    private function readManufacturersTyres()
    {
        $manufacturersTyres = [];
        if (file_exists($this->filenameManufacturersTyres)) {
            $manufacturersTyres = $this->jsonDecode(file_get_contents($this->filenameManufacturersTyres));
        }

        return $manufacturersTyres;
    }

    private function readPriceList()
    {
        $priceList = [];
        if (file_exists($this->filenamePriceList)) {
            $priceList = $this->jsonDecode(file_get_contents($this->filenamePriceList));
        }

        return $priceList;
    }

    public function getRowsCount($filename)
    {
        if (!$filename) {
            return 0;
        }

        switch ($filename) {
            case 'tyres':
                $filename = $this->filenameTyres;
                break;
            case 'manufacturers':
                $filename = $this->filenameManufacturers;
                break;
            case 'manufacturers_tyres':
                $filename = $this->filenameManufacturersTyres;
                break;
            case 'price_list':
                $filename = $this->filenamePriceList;
                break;
            default:
                return 0;
        }

        if (file_exists($filename)) {
            $fileContent = file_get_contents($filename);
            try {
                $items = json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $th) {
                $items = [];
            }
        } else {
            $items = [];
        }

        if ($filename == $this->filenamePriceList) {
            $suppliers = [];
            foreach ($items as $item) {
                $suppliers[$item['id']] = 1;
            }
            return count($suppliers);
        }

        return count($items);
    }


    /**
     * Read Json File
     * @return array|false
     */
    public function read($filename, $offset = 0, $limit = null)
    {
        if (!$filename) {
            return false;
        }

        switch ($filename) {
            case 'tyres':
                $filename = $this->filenameTyres;
                break;
            case 'manufacturers':
                $filename = $this->filenameManufacturers;
                break;
            case 'manufacturers_tyres':
                $filename = $this->filenameManufacturersTyres;
                break;
            case 'price_list':
                $filename = $this->filenamePriceList;
                break;
            default:
                return false;
        }

        if (file_exists($filename)) {
            if ($offset == 0 && $limit == null) {
                return $this->jsonDecode(file_get_contents($filename));
            } else {
                return $this->jsonDecode(file_get_contents($filename), $offset, $limit);
            }
        }

        return [];
    }

    /**
     * Json Decode
     * @param mixed $content
     * @return array|false
     */
    public function jsonDecode($content, $offset = 0, $limit = null)
    {
        try {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return array_slice($content, $offset, $limit);
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
            return false;
        }
    }

    public function delete($filename)
    {
        if (!$filename) {
            return false;
        }

        switch ($filename) {
            case 'tyres':
                $filename = $this->filenameTyres;
                break;
            case 'manufacturers':
                $filename = $this->filenameManufacturers;
                break;
            case 'manufacturers_tyres':
                $filename = $this->filenameManufacturersTyres;
                break;
            case 'price_list':
                $filename = $this->filenamePriceList;
                break;
            default:
                return false;
        }

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    /**
     * Analizza un file JSON grande contenente un array di oggetti, elaborando un elemento alla volta
     * 
     * @param string $filename Il percorso del file JSON da analizzare
     * @param callable $callback La funzione da chiamare per ogni elemento
     * @param int $offset Numero di elementi da saltare all'inizio
     * @param int $limit Numero massimo di elementi da elaborare (0 = nessun limite)
     * @return array['offset', 'limit', 'count']
     */
    public function parseJsonArray($filename, $callback, $offset = 0, $limit = 0): array
    {
        if ($filename == null) {
            return [
                'offset' => 0,
                'limit' => $limit,
                'count' => 0,
            ];
        }

        switch ($filename) {
            case 'tyres':
                $filename = $this->filenameTyres;
                break;
            case 'manufacturers':
                $filename = $this->filenameManufacturers;
                break;
            case 'manufacturers_tyres':
                $filename = $this->filenameManufacturersTyres;
                break;
            case 'price_list':
                $filename = $this->filenamePriceList;
                break;
            default:
                return [
                    'offset' => 0,
                    'limit' => $limit,
                    'count' => 0,
                ];
        }

        $handle = fopen($filename, 'r');

        // Salta il primo carattere (dovrebbe essere '[')
        fseek($handle, 1);

        $buffer = '';
        $depth = 0;
        $inString = false;
        $escape = false;
        $itemCount = 0;
        $processedCount = 0;

        while (!feof($handle)) {
            $char = fgetc($handle);

            if ($escape) {
                $escape = false;
            } else if ($char === '\\') {
                $escape = true;
            } else if ($char === '"') {
                $inString = !$inString;
            } else if (!$inString) {
                if ($char === '{') {
                    $depth++;
                } else if ($char === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $buffer .= $char;
                        $itemCount++;

                        // Elabora l'elemento solo se abbiamo superato l'offset
                        if ($itemCount > $offset) {
                            $item = json_decode($buffer, true);
                            $callback($item, $itemCount - 1); // Passa anche l'indice (0-based)
                            $processedCount++;

                            // Esci se abbiamo raggiunto il limite
                            if ($limit > 0 && $processedCount >= $limit) {
                                break;
                            }
                        }

                        $buffer = '';

                        // Salta virgola e spazi
                        while (($nextChar = fgetc($handle)) !== false) {
                            if ($nextChar === '{') {
                                $buffer .= $nextChar;
                                $depth = 1;
                                break;
                            } else if ($nextChar === ']') {
                                // Fine dell'array
                                break 2;
                            }
                        }
                        continue;
                    }
                }
            }

            if ($depth > 0 || $char === '{') {
                $buffer .= $char;
            }
        }

        fclose($handle);
        return [
            'offset' => $processedCount ? $processedCount + $offset : $processedCount,
            'limit' => $limit,
            'count' => $offset + $processedCount,
            'rows' => $buffer ? json_decode($buffer, true) : [],
        ];
    }
}