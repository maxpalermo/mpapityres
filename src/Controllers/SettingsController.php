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

namespace MpSoft\MpApiTyres\Controllers;

use MpSoft\MpApiTyres\Helpers\CreateInsertQueryFromJson;
use MpSoft\MpApiTyres\Helpers\CurlCatalogDownload;
use MpSoft\MpApiTyres\Helpers\ParseTyreCsvHelper;
use MpSoft\MpApiTyres\Models\ProductTyre;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends FrameworkBundleAdminController
{
    public function index2()
    {
        $html = $this->renderView(
            '@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/settings.html.twig',
            []
        );

        return $this->json($html);
    }

    public function index()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');

        // Recupera il token API
        $apiTyres14Token = \Configuration::get('MPAPITYRES_API_TOKEN') ?: '';
        $apiProductsToken = \Configuration::get('MPAPITYRES_API_PRODUCTS_TOKEN') ?: '';
        $apiAlloysToken = \Configuration::get('MPAPITYRES_API_ALLOYS_TOKEN') ?: '';
        $apiWearPartsToken = \Configuration::get('MPAPITYRES_API_WEAR_PARTS_TOKEN') ?: '';

        // Recupera i filtri
        $filters = \Configuration::get('MPAPITYRES_FILTERS') ?: '[]';
        $filters = json_decode($filters, true);

        // Recupera le traduzioni dei campi
        $adminController = new AdminMpApiTyresController();
        $translations = $adminController->getFieldTranslations();

        // Recupera le caratteristiche
        $id_lang = (int) \Context::getContext()->language->id;
        $features = $connection->executeQuery(
            "SELECT * FROM {$pfx}feature_lang WHERE id_lang = {$id_lang} ORDER BY name ASC"
        )->fetchAllAssociative();

        $html = $this->renderView(
            '@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/settings.html.twig',
            [
                'apiTyres14Token' => $apiTyres14Token,
                'apiProductsToken' => $apiProductsToken,
                'apiAlloysToken' => $apiAlloysToken,
                'apiWearPartsToken' => $apiWearPartsToken,
                'filters' => $filters,
                'translations' => $translations,
                'features' => $features,
                'settings' => [
                    'auto_import' => \Configuration::get('MPAPITYRES_AUTO_IMPORT') ?: '0',
                    'import_frequency' => \Configuration::get('MPAPITYRES_IMPORT_FREQUENCY') ?: 'daily',
                    'last_import' => \Configuration::get('MPAPITYRES_LAST_IMPORT') ?: '',
                ]
            ]
        );

        return $this->json($html);
    }
}
