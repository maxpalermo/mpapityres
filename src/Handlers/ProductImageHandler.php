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

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Image\ImageFormatConfiguration;

class ProductImageHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getImageInfo($imgPath, $getContent = false)
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

    /**
     * @param string $imgPath
     * @return array [path, filename, content, filesize, mime-type, suffix] or false
     */
    public function getImageContent($imgPath, $legend = null): array|false
    {
        $imageContent = file_get_contents($imgPath);
        $imageType = false;
        if ($imageContent !== false) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $imgPath);
                finfo_close($finfo);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error getting image content: ' . $th->getMessage());
                $mimeType = 'image/jpeg';
            }

            try {
                $filesize = filesize($imgPath);
            } catch (\Throwable $th) {
                \PrestaShopLogger::addLog('Error getting image content: ' . $th->getMessage());
                $filesize = 0;
            }

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
            'path' => $imgPath,
            'filename' => basename($imgPath),
            'filesize' => $filesize,
            'content' => $imageContent,
            'mime-type' => $mimeType,
            'suffix' => $imageType,
            'legend' => $legend,
        ];
    }

    /**
     * @param int $idProduct
     * @param string $imgPath
     * @return bool
     */
    public function addProductImage(\Product $product, $imgPath): array|false
    {
        $context = \Context::getContext();
        $id_lang = (int) \Context::getContext()->language->id;
        $max_upload = (int) ini_get('upload_max_filesize');
        $max_post = (int) ini_get('post_max_size');
        $max_image_size = min($max_upload, $max_post);

        if (!\Validate::isLoadedObject($product)) {
            return false;
        }

        $imageContent = $this->getImageContent($imgPath, $product->name);
        if (!$imageContent) {
            return false;
        }

        $image = $this->createImage($imageContent, $product->id);

        if (\Validate::isLoadedObject($image)) {
            //Salvo l'immagine nella cartella img/p
            $imagePath = $image->getPathForCreation();

            $upload = $this->upload(
                $imageContent['path'],
                $imagePath,
                $imageContent['mime-type'],
                $imageContent['content']
            );

            $sourceFile = $upload['dest'];
            $destFile = $imagePath;
            $error = null;

            if (!\ImageManager::resize($sourceFile, $destFile, null, null, 'jpg', false, $error)) {
                switch ($error) {
                    case \ImageManagerCore::ERROR_FILE_NOT_EXIST:
                        $upload['error'] = 'An error occurred while copying image, the file does not exist anymore.';

                        break;

                    case \ImageManagerCore::ERROR_FILE_WIDTH:
                        $upload['error'] = 'An error occurred while copying image, the file width is 0px.';

                        break;

                    case \ImageManagerCore::ERROR_MEMORY_LIMIT:
                        $upload['error'] = 'An error occurred while copying image, check your memory limit.';

                        break;

                    default:
                        $upload['error'] = 'An error occurred while copying the image.';

                        break;
                }
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
                                $sourceFile,
                                $destFile . '-' . stripslashes($imageType['name']) . '.' . $imageFormat,
                                $imageType['width'],
                                $imageType['height'],
                                $imageFormat,
                                $forceFormat
                            )
                        ) {
                            $upload['error'] = 'An error occurred while copying this image:' . ' ' . stripslashes($imageType['name']);

                            continue;
                        }

                        if ($generate_hight_dpi_images) {
                            if (
                                !\ImageManager::resize(
                                    $sourceFile,
                                    $destFile . '-' . stripslashes($imageType['name']) . '2x.' . $imageFormat,
                                    (int) $imageType['width'] * 2,
                                    (int) $imageType['height'] * 2,
                                    $imageFormat,
                                    $forceFormat
                                )
                            ) {
                                $upload['error'] = 'An error occurred while copying this image:' . ' ' . stripslashes($imageType['name']);

                                continue;
                            }
                        }
                    }
                }
            }

            unlink($destFile);
            //Necesary to prevent hacking
            unset($upload['dest']);
            \Hook::exec('actionWatermark', ['id_image' => $image->id, 'id_product' => $product->id]);

            if (!$image->update()) {
                $upload['error'] = 'Error while updating the status.';

                return false;
            }

            // Associate image to shop from context
            $shops = \Shop::getContextListShopID();
            $image->associateTo($shops);
            $json_shops = [];

            foreach ($shops as $id_shop) {
                $json_shops[$id_shop] = true;
            }

            $upload['status'] = 'ok';
            $upload['id'] = $image->id;
            $upload['position'] = $image->position;
            $upload['cover'] = $image->cover;
            $upload['legend'] = $image->legend;
            $upload['path'] = $image->getExistingImgPath();
            $upload['shops'] = $json_shops;

            @unlink(_PS_TMP_IMG_DIR_ . 'product_' . (int) $product->id . '.jpg');
            @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $product->id . '_' . $context->shop->id . '.jpg');
        }

        return $upload;
    }

    public function createImage($imageContent, $idProduct): \Image|false
    {
        $languages = \Language::getLanguages();

        $image = new \Image();
        $image->id_product = (int) $idProduct;
        $image->position = \Image::getHighestPosition($idProduct) + 1;
        $image->cover = !\Image::getCover($image->id_product);
        foreach ($languages as $language) {
            $image->legend[$language['id_lang']] = $imageContent['legend'][$language['id_lang']] ?? 'Pneumatico';
        }

        if (($validate = $image->validateFieldsLang(false, true)) !== true) {
            return $validate;
        }

        $result = $image->add();
        if (!$result) {
            return false;
        }

        return $image;
    }

    public function upload($source, $dest, $mime, $content = null): array|false
    {
        $this->ensureDirectoryExists($dest);

        if ($content === null && !$source['content']) {
            $content = file_get_contents($source['tmp_name']);
        }

        if ($content === false) {
            return false;
        }

        switch ($mime) {
            case 'image/jpeg':
                $suffix = 'jpg';
                break;
            case 'image/png':
                $suffix = 'png';
                break;
            case 'image/webp':
                $suffix = 'webp';
                break;
            default:
                $suffix = 'jpg';
                break;
        }

        $dest .= '.' . $suffix;

        $result = file_put_contents($dest, $content);

        if ($result === false) {
            return false;
        }

        $fileSize = filesize($dest);

        return [
            'dest' => $dest,
            'size' => $fileSize,
            'mime' => $mime,
            'suffix' => $suffix,
        ];
    }

    /**
     * Verifica che la directory di destinazione esista, altrimenti la crea ricorsivamente.
     * @param string $filePath Percorso completo del file (es: /var/htm/img/p/1/3/5/7/1357.jpg)
     * @param int $permissions Permessi della cartella (default 0755)
     * @return bool True se la directory esiste o è stata creata con successo, false altrimenti
     */
    function ensureDirectoryExists($filePath, $permissions = 0755)
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            return mkdir($directory, $permissions, true);
        }
        return true;
    }
}
