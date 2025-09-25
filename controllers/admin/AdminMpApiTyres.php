<?php

use MpSoft\MpApiTyres\Const\Constants;
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

use MpSoft\MpApiTyres\Traits\DownloadCsvTrait;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class AdminMpApiTyresController extends ModuleAdminController
{
    use DownloadCsvTrait;

    public function initContent()
    {
        $action = Tools::getValue('action', 'default');
        if ($action && preg_match('/Action$/', $action) && method_exists($this, $action)) {
            return $this->$action();
        }

        switch ($action) {
            case 'showSettings':
                $this->content = $this->showSettingsPage();
                break;
            case 'default':
            default:
                $this->content = $this->showMainPage();

        }

        return parent::initContent();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->context->smarty->assign('baseUrl', $this->context->link->getBaseLink());
        $this->addJqueryUI('ui.widget');
        $this->addJqueryUI('ui.mouse');
        $this->addCSS($this->module->getLocalPath() . 'views/assets/components/select2/select2.min.css');
        $this->addCSS($this->module->getLocalPath() . 'views/assets/components/select2/select2.bs4-theme.min.css');
        $this->addJS($this->module->getLocalPath() . 'views/assets/components/select2/select2.min.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/progressDownloadCsv.js');
        $this->addCSS($this->module->getLocalPath() . 'views/assets/css/admin.css', 'all', 9999);
    }

    protected function redirectToRoute()
    {
        $route = Tools::getValue('route');
        $method = Tools::getValue('method');
        // Reindirizza alla dashboard Symfony
        Tools::redirectAdmin($this->getAdminLink($route, $method));
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $action = Tools::getValue('action', 'default');
        if ($action == 'default') {
            $this->page_header_toolbar_btn['show-settings'] = [
                'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showSettings']),
                'desc' => $this->trans('Impostazioni'),
                'icon' => 'process-icon-cogs',
                'class' => 'btn-show-settings',
            ];
        }
        if ($action == 'showSettings') {
            $this->page_header_toolbar_btn['show-import'] = [
                'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'default']),
                'desc' => $this->trans('Pagina di importazione'),
                'icon' => 'process-icon-download',
                'class' => 'btn-main-page',
            ];

        }
        unset($this->page_header_toolbar_btn['new']);
        unset($this->page_header_toolbar_btn['delete']);
    }

    public function getAdminLink($controller, $method = 'index')
    {
        $moduleName = $this->module->name;
        $controllerRoterName = "{$moduleName}_{$controller}_{$method}";
        return SymfonyContainer::getInstance()
            ->get('router')
            ->generate($controllerRoterName);
    }

    protected function showSettingsPage()
    {
        $path = 'AdminController/settings.html.twig';
        $params = $this->getSettings();
        return $this->setTpl($path, $params);
    }

    protected function showMainPage()
    {
        $path = 'AdminController/mainPage.html.twig';
        $params = $this->getMainPageTplVars();
        return $this->setTpl($path, $params);
    }

    protected function setTpl($path, $params = [])
    {
        $baseViews = $this->module->getLocalPath() . 'views/twig/';
        $template = "{$baseViews}{$path}";
        if (!file_exists($template)) {
            return '<div class="alert alert-danger">Template not found: ' . $template . '</div>';
        }

        try {
            /** @var \Twig\Environment $twig */
            $twig = $this->module->get('twig');

            // Aggiungiamo un loader per il percorso specifico dei nostri template
            $loader = new \Twig\Loader\FilesystemLoader($this->module->getLocalPath() . 'views/twig/');
            $newTwig = new \Twig\Environment($loader);

            // Estraiamo il nome del file dal percorso completo
            $relativePath = str_replace($this->module->getLocalPath() . 'views/twig/', '', $template);

            return $newTwig->render($relativePath, $params);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Error rendering template: ' . $e->getMessage() . '</div>';
        }

        /*
        $tpl = $this->context->smarty->createTemplate($path);
        $tpl->assign($params);
        return $tpl->fetch();
        */
    }

    protected function getSettings()
    {
        $settings = [
            'baseUrl' => $this->context->link->getBaseLink(),
            'downloadCatalogActionUrl' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'downloadCatalog']),
            'importCatalogActionUrl' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'importCatalog']),
            'last50Tyres' => json_encode($this->getLast50Tyres()),
            'manufacturers' => $this->getManufacturers(),
            'totalManufacturers' => $this->getTotalManufacturers(),
            'totalTyres' => $this->getTotalTyres(),
            'totalSuppliers' => $this->getTotalSuppliers(),
            'filters' => $this->getFilters(),
            'taxRulesGroups' => $this->getTaxRulesGroups(),
            'categoryName' => $this->getCategoryName(),
            'idTaxRulesGroup' => $this->getIdTaxRulesGroup(),
            'host_api' => \Configuration::get(Constants::MPAPITYRES_HOST) ?: 'https://tyre24.alzura.com/it/it/rest/V14/tyres',
            'token_api' => \Configuration::get(Constants::MPAPITYRES_TOKEN) ?: '',
            'cron_download' => $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'getCatalogAction', 'ajax' => 1], true),
            'cron_import' => $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'createPrestashopCatalogAction', 'ajax' => 1], true),
            'cron_pause' => \Configuration::get(Constants::MPAPITYRES_CRON_TIME_BETWEEN_UPDATES) ?: 120,
        ];

        return $settings;
    }

    protected function getMainPageTplVars()
    {
        $downloadCatalogActionUrl = $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'getCatalogAction', 'ajax' => 1], true);
        $importCatalogActionUrl = $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'createPrestashopCatalogAction', 'ajax' => 1], true);
        $reloadImagesActionUrl = $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'reloadImagesAction', 'ajax' => 1], true);
        $deleteProductsActionUrl = $this->context->link->getModuleLink($this->module->name, 'Cron', ['action' => 'deleteProductsAction', 'ajax' => 1], true);

        return [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'default']),
            'downloadCatalogActionUrl' => $downloadCatalogActionUrl,
            'importCatalogActionUrl' => $importCatalogActionUrl,
            'reloadImagesActionUrl' => $reloadImagesActionUrl,
            'deleteProductsActionUrl' => $deleteProductsActionUrl,
            'summary' => $this->getSummary(),
        ];
    }

    protected function getSummary()
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        //Prodotti totali
        $queryTotalProducts = "SELECT COUNT(*) as product FROM {$pfx}product";
        $totalProducts = $db->getValue($queryTotalProducts);
        //Prodotti disattivati
        $queryDisabledProducts = "SELECT COUNT(*) as disabled FROM {$pfx}product WHERE active = 0";
        $disabledProducts = $db->getValue($queryDisabledProducts);
        //Product Tyre
        $queryProductTyre = "SELECT COUNT(*) as product_tyre FROM {$pfx}product_tyre";
        $productTyre = $db->getValue($queryProductTyre);
        //Produttori
        $queryManufacturers = "SELECT COUNT(*) as manufacturer FROM {$pfx}manufacturer";
        $manufacturers = $db->getValue($queryManufacturers);
        //Fornitori
        $features = \Feature::getFeatures($id_lang);
        //Ultimo fetch download
        $lastDateDownload = Configuration::get(Constants::MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE) ?: "";
        //Ultimo fetch import
        $lastDateImport = Configuration::get(Constants::MPAPITYRES_CRON_IMPORT_UPDATED_DATE) ?: "";
        return [
            'products_count' => $totalProducts,
            'products_disabled' => $disabledProducts,
            'products_tyre_count' => $productTyre,
            'manufacturers_count' => $manufacturers,
            'features' => $features,
            'last_fetch_download' => $lastDateDownload,
            'last_fetch_import' => $lastDateImport,
        ];
    }

    private function getLast50Tyres()
    {
        return [];
    }

    private function getManufacturers()
    {
        return \Manufacturer::getManufacturers();
    }

    private function getTaxRulesGroups()
    {
        $id_lang = (int) \Context::getContext()->language->id;
        return \TaxRulesGroup::getTaxRulesGroups($id_lang);
    }

    private function getTotalManufacturers()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}manufacturer";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalProducts()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}product";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalSuppliers()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}supplier";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    private function getTotalTyres()
    {
        $pfx = _DB_PREFIX_;
        $connection = $this->get('doctrine.dbal.default_connection');
        $query = "SELECT COUNT(*) FROM {$pfx}product_tyre";
        $result = $connection->fetchAssociative($query);
        return $result['COUNT(*)'];
    }

    protected function getFilters()
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

        return $data;
    }

    private function getCategoryName()
    {
        return \Configuration::get(Constants::MPAPITYRES_CATEGORY_NAME);
    }

    private function getIdTaxRulesGroup()
    {
        return \Configuration::get(Constants::MPAPITYRES_ID_TAX_RULES_GROUP);
    }

    public function postProcess()
    {
        $params = Tools::getAllValues();

        if (isset($params['form-action']) && $params['form-action'] == 'saveSettings') {
            $this->saveSettingsApi($params);
            $this->saveSettingFilters($params);
            $this->saveSettingProducts($params);
        }

        return true;
    }

    private function saveSettingsApi($params)
    {
        $host = $params['host-api'];
        $token = $params['token-api'];
        $pause = $params['cron-pause'];

        \Configuration::updateValue(Constants::MPAPITYRES_HOST, $host);
        \Configuration::updateValue(Constants::MPAPITYRES_TOKEN, $token);
        \Configuration::updateValue(Constants::MPAPITYRES_CRON_TIME_BETWEEN_UPDATES, $pause);

        return true;
    }

    private function saveSettingProducts($params)
    {
        $category_name = $params['category'];
        $id_tax_rules_group = $params['id_tax_rules_group'];

        \Configuration::updateValue(Constants::MPAPITYRES_CATEGORY_NAME, $category_name);
        \Configuration::updateValue(Constants::MPAPITYRES_ID_TAX_RULES_GROUP, $id_tax_rules_group);

        return true;
    }

    private function saveSettingFilters($params)
    {
        $filter0 = $params['filter-0'];
        $filter1 = $params['filter-1'];
        $filter2 = $params['filter-2'];
        $filter4 = $params['filter-4'];
        $filter5 = $params['filter-5'];
        $filter6 = $params['filter-6'];

        $filename = _PS_MODULE_DIR_ . 'mpapityres' . '/views/assets/json/api_filters.json';

        $data = [
            'filter-0' => $filter0,
            'filter-1' => $filter1,
            'filter-2' => $filter2,
            'filter-4' => $filter4,
            'filter-5' => $filter5,
            'filter-6' => $filter6,
        ];
        $result = file_put_contents($filename, json_encode($data));

        return [
            'success' => $result,
        ];
    }
}

