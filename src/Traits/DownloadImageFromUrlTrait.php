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

namespace MpSoft\MpApiTyres\Traits;

trait DownloadImageFromUrlTrait
{

    public static function checkImageHash($imageHash)
    {
        $defaultNoImageHash = \Configuration::get('MPAPITYRES_NO_IMAGE_HASH');
        $defaultNoLabelHash = \Configuration::get('MPAPITYRES_NO_LABEL_HASH');

        return $defaultNoImageHash === $imageHash || $defaultNoLabelHash === $imageHash;
    }

    public static function guzzleDownload($url, $base64 = true)
    {
        $client = new \GuzzleHttp\Client();
        // Send a GET request to download the image
        try {
            $response = $client->get($url, [
                'http_errors' => false,
                'timeout' => 20,
                'connect_timeout' => 10,
                'verify' => false,
            ]);
        } catch (\Throwable $th) {
            return false;
        }

        if ($response->getStatusCode() >= 500) {
            return false;
        }

        // Get the content of the downloaded image
        $imageContent = $response->getBody()->getContents();


        return $base64 ? base64_encode($imageContent) : $imageContent;
    }

    /**
     * Scarica più immagini in modo asincrono utilizzando GuzzleHttp
     * 
     * @param array $urls Array di URL da scaricare
     * @param bool $base64 Se true, restituisce i contenuti in formato base64
     * @param int $concurrency Numero massimo di richieste simultanee (default: 5)
     * @return array Array associativo con URL come chiave e contenuto come valore
     */
    public static function guzzleDownloadArray($urls, $base64 = true, $concurrency = 5)
    {
        $client = new \GuzzleHttp\Client([
            'http_errors' => false,
            'timeout' => 20,
            'connect_timeout' => 10,
            'verify' => false,
        ]);

        $results = [];

        // Crea le richieste
        $requests = function ($urls) use ($client) {
            foreach ($urls as $url) {
                // Yield una richiesta per ogni URL
                yield $url => function () use ($client, $url) {
                    return $client->getAsync($url);
                };
            }
        };

        // Crea il pool con le richieste
        $pool = new \GuzzleHttp\Pool($client, $requests($urls), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use (&$results, $base64) {
                if ($response->getStatusCode() < 500) {
                    $imageContent = $response->getBody()->getContents();
                    $results[$index] = $base64 ? base64_encode($imageContent) : $imageContent;
                } else {
                    $results[$index] = false;
                }
            },
            'rejected' => function ($reason, $index) use (&$results) {
                $results[$index] = false;
            },
        ]);

        // Attendi il completamento di tutte le richieste
        $pool->promise()->wait();

        return $results;
    }

    /**
     * Scarica un'immagine da una URL e restituisce i dati
     * @param mixed $url
     * @return array {success: bool, error: string, http_code: mixed, content_type: string, image_type: mixed|string|null, content: base64(string), final_url: string}
     */
    public static function downloadImageFromUrlStatic($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($curlErr || $http_code < 200 || $http_code >= 400) {
            return [
                'success' => false,
                'error' => $curlErr ?: "HTTP $http_code",
                'http_code' => $http_code,
                'content_type' => $content_type,
                'image_type' => 'unknown',
                'content' => '',
                'final_url' => $final_url,
            ];
        }

        $body = substr($response, $header_size);

        // Verifica se il contenuto è un'immagine
        $image_type = null;
        if (preg_match('#^image/(.+)$#i', $content_type, $matches)) {
            $image_type = $matches[1];
        } else {
            // tentativo di determinare il tipo da getimagesizefromstring
            $imginfo = @getimagesizefromstring($body);
            if ($imginfo && isset($imginfo['mime']) && preg_match('#^image/(.+)$#i', $imginfo['mime'], $matches)) {
                $image_type = $matches[1];
            }
        }

        return [
            'success' => (bool) $image_type,
            'error' => 0,
            'http_code' => $http_code,
            'content_type' => $content_type,
            'image_type' => $image_type,
            'content' => base64_encode($body),
            'final_url' => $final_url,
        ];
    }

    public static function isValidImageUrlStatic($url)
    {
        // Verifica se l'URL è sintatticamente valido
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Inizializza cURL per verificare l'header della risposta
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Non scaricare il corpo della risposta
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout in secondi
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignora la verifica SSL se necessario

        $result = curl_exec($ch);

        if ($result === false) {
            curl_close($ch);
            return [
                'httpCode' => "NO RESULT",
                'contentType' => "NO RESULT",
                'isValid' => false,
                'url' => $url,
            ];
        }

        // Ottieni il codice di stato HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Ottieni il content type
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        // Verifica se il codice di stato è 200 (OK) e se il content type indica un'immagine
        return [
            'httpCode' => $httpCode,
            'contentType' => $contentType,
            'isValid' => strpos($contentType, 'image/') === 0,
            'url' => $url,
        ];
    }

    /**
     * Aggiunge un'immagine a un prodotto
     * 
     * @param int $id_product ID del prodotto
     * @param string $image_url URL dell'immagine
     * @return array {status: string, id_product: int, url: string, httpCode: int, contentType: string, message: string}
     */
    public static function addProductImageStatic($id_product, $image_url)
    {
        $hasCover = (int) self::hasCover($id_product);

        // Scarico l'immagine dal web
        //$imageCurl = self::downloadImageFromUrlStatic($image_url);
        $imageContent = self::guzzleDownload($image_url);

        if ($imageContent === false) {
            $error = error_get_last();
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $error['type'],
                'error_message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => "Immagine non scaricata: {$image_url}",
            ];
        }

        //Faccio l'hashing dell'immagine
        $image_hash = md5($imageContent);

        //Controllo se l'immagine esiste già
        if (self::checkImageHash($image_hash)) {
            $error = error_get_last();
            return [
                'status' => 'OK',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $error['type'],
                'error_message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'httpCode' => 200,
                'contentType' => "NO-IMAGE-CONTENT",
                'message' => "Nessuna immagine trovata: {$image_url}",
            ];
        }

        try {
            // Creo un'istanza dell'immagine
            $image = new \Image();
            $image->id_product = (int) $id_product;
            $image->position = \Image::getHighestPosition($id_product) + 1;
            $image->cover = !$hasCover;

            // Salvo l'immagine nel database
            \Db::getInstance()->displayError(true);
            if (!$image->add()) {
                \Db::getInstance()->displayError(true);
                return [
                    'status' => 'ERROR',
                    'id_product' => $id_product,
                    'url' => $image_url,
                    'error' => \Db::getInstance()->getNumberError(),
                    'error_message' => \Db::getInstance()->getMsgError(),
                    'httpCode' => 500,
                    'contentType' => 'text/plain',
                    'message' => "Classe Image->add() " . \Db::getInstance()->getMsgError(),
                ];
            }

            if ($imageContent) {
                //Salvo il contenuto dell'immagine nella cartella
                $image_path = $image->getPathForCreation();
                $imageContent = base64_decode($imageContent);
                $destOriginalImage = "{$image_path}.jpg";
                if (!file_put_contents($destOriginalImage, $imageContent)) {
                    $error = error_get_last();
                    $errorCode = $error['type'] ?? 0;
                    $errorMessage = $error['message'] ?? 'Sconosciuto';
                    return [
                        'status' => 'ERROR',
                        'id_product' => $id_product,
                        'url' => $image_url,
                        'error' => $errorCode,
                        'error_message' => $errorMessage,
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'httpCode' => 500,
                        'contentType' => 'text/plain',
                        'message' => "Immagine non salvata: file_puts_content failed, {$errorMessage}",
                    ];
                }

                // Genero le diverse dimensioni dell'immagine
                $image_types = \ImageType::getImagesTypes('products');

                // Genero i thumbnails
                foreach ($image_types as $image_type) {
                    $destName = "{$image_path}-{$image_type['name']}.jpg";
                    $resized = \ImageManager::resize(
                        $destOriginalImage,
                        $destName,
                        $image_type['width'],
                        $image_type['height']
                    );

                    if (!$resized) {
                        \PrestaShopLogger::addLog("Immagine non salvata: ImageManager::resize failed format {$image_type['name']}");
                    }
                }

                return [
                    'status' => 'OK',
                    'id_product' => $id_product,
                    'url' => $image_url,
                    'error' => 0,
                    'error_message' => '',
                    'file' => '',
                    'line' => '',
                    'httpCode' => 200,
                    'contentType' => 'text/plain',
                    'message' => "Immagine salvata",
                ];
            }

            \Db::getInstance()->displayError(true);
            $error = error_get_last();
            $errorCode = $error['type'] ?? 0;
            $errorMessage = $error['message'] ?? 'Sconosciuto';
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $errorCode,
                'error_message' => $errorMessage,
                'file' => $error['file'],
                'line' => $error['line'],
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => "Immagine non salvata: " . $errorMessage,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'id_product' => $id_product,
                'url' => $image_url,
                'error' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'httpCode' => 500,
                'contentType' => 'text/plain',
                'message' => "Immagine non salvata: " . $e->getMessage(),
            ];
        }
    }

    public static function createTyreUrl($idT24)
    {
        $baseUrl = 'https://media1.tyre-shopping.com/images_ts/tyre';
        $encoded = "MzIwMzAx";
        $size = "w800-h800";
        $userCode = '24000320301';
        $url = "{$baseUrl}/{$idT24}-{$encoded}-{$size}-br1-{$userCode}.jpg";

        return $url;
    }

    public static function createTyreUrlOriginal($idT24)
    {
        $baseUrl = 'https://media1.tyre-shopping.com/images/tyre';
        $url = "{$baseUrl}/{$idT24}.jpg";

        return $url;
    }

    public static function hasImages($id_product)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $query->select("count(id_product)")
            ->from('image')
            ->where('id_product=' . (int) $id_product);

        return (int) $db->getValue($query);
    }


    public static function hasCover($id_product)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $query->select("id_image")
            ->from('image')
            ->where('id_product=' . (int) $id_product)
            ->where('cover=1');

        return (int) $db->getValue($query);
    }

    public static function compareHashImage($image_path, &$content)
    {
        $content = file_get_contents($image_path);
        $hash = md5($content);
        $check = \Configuration::get('MPAPITYRES_NO_IMAGE_HASH');

        return $hash === $check;
    }
}
