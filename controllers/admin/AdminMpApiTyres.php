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

use MpSoft\MpApiTyres\Configuration\ConfigValues;
use MpSoft\MpApiTyres\Const\Constants;
use MpSoft\MpApiTyres\Helpers\LoadPriceHelper;
use MpSoft\MpApiTyres\Import\CreatePfu;
use MpSoft\MpApiTyres\Models\ModelProductTyre;
use MpSoft\MpApiTyres\Traits\ResponseTrait;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class AdminMpApiTyresController extends ModuleAdminController
{
    use ResponseTrait;

    private $id_lang;
    private $configValues;
    private $message;

    public function __construct()
    {
        parent::__construct();
        $this->configValues = ConfigValues::getInstance();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.widget');
        $this->addJqueryUI('ui.mouse');
        $this->addJqueryUI('chosen');

        $this->addCSS($this->module->getLocalPath() . 'views/assets/css/admin.css', 'all', 9999);
        $this->addJS($this->module->getLocalPath() . 'views/assets/components/select2/select2.min.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/progressDownloadCsv.js');

        $this->addJS('https://cdn.jsdelivr.net/npm/chart.js@2.9.4');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/FetchManager.js');

        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/ShowModalDialog.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/FetchApiJson.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/FetchCsvJson.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/ImportJsonCatalog.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/ReloadImages.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/classes/CreatePfu.js');

        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/mainPage.js');
        $this->addJS($this->module->getLocalPath() . 'views/assets/js/Controllers/AdminMpApiTyres/nobootstrap-remove.js');
    }

    public function initContent()
    {
        $this->id_lang = $this->context->language->id;
        $action = Tools::getValue('action', 'default');
        if ($action && preg_match('/Action$/', $action) && method_exists($this, $action)) {
            return $this->$action();
        }

        $disableJqueryMigrateLog = '
            <script>
                jQuery.migrateMute = true;
                jQuery.migrateTrace = false;
            </script>
        ';

        switch ($action) {
            case 'showSettings':
                $this->content = $disableJqueryMigrateLog . $this->message . $this->showSettingsPage();
                break;
            case 'showPfu':
                $this->content = $disableJqueryMigrateLog . $this->message . $this->showPfuPage();
                break;
            case 'default':
            default:
                $this->content = $disableJqueryMigrateLog . $this->showMainPage();
        }

        return parent::initContent();
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
            $this->getToolbarBtn('default');
            $this->getToolbarBtn('pfu');
        }
        if ($action == 'showSettings') {
            $this->getToolbarBtn('settings');
            $this->getToolbarBtn('pfu');
        }
        if ($action == 'showPfu') {
            $this->getToolbarBtn('default');
            $this->getToolbarBtn('settings');
        }

        unset($this->page_header_toolbar_btn['new']);
        unset($this->page_header_toolbar_btn['delete']);
    }

    protected function getToolbarBtn($page)
    {
        switch ($page) {
            case 'default':
                $this->page_header_toolbar_btn['show-settings'] = [
                    'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showSettings']),
                    'desc' => $this->trans('Impostazioni'),
                    'icon' => 'process-icon-cogs',
                    'class' => 'btn-show-settings',
                ];
                break;
            case 'settings':
                $this->page_header_toolbar_btn['show-import'] = [
                    'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'default']),
                    'desc' => $this->trans('Pagina di importazione'),
                    'icon' => 'process-icon-download',
                    'class' => 'btn-main-page',
                ];
                break;
            case 'pfu':
                $this->page_header_toolbar_btn['show-pfu'] = [
                    'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showPfu']),
                    'desc' => $this->trans('Associa i PFU'),
                    'icon' => 'process-icon-compress',
                    'class' => 'btn-show-pfu',
                ];
                break;
        }
    }

    public function getAdminLink($controller, $method = 'index')
    {
        $moduleName = $this->module->name;
        $controllerRouterName = "{$moduleName}_{$controller}_{$method}";
        return SymfonyContainer::getInstance()
            ->get('router')
            ->generate($controllerRouterName);
    }

    protected function getSmartyPath($path)
    {
        return $this->module->getLocalPath() . 'views/smarty/' . $path;
    }

    protected function getTwigPath($path)
    {
        return $this->module->getLocalPath() . 'views/twig/' . $path;
    }

    protected function showSettingsPage()
    {
        $path = '@twig/AdminController/settings.html.twig';
        $params = $this->getSettingsPageTplVars();
        return $this->setTpl($path, $params);
    }

    protected function showPfuPage()
    {
        $path = '@twig/AdminController/pfu.html.twig';
        $params = $this->getPfuPageTplVars();
        return $this->setTpl($path, $params);
    }

    protected function showMainPage()
    {
        $path = '@twig/AdminController/mainPage.html.twig';
        $params = $this->getMainPageTplVars();
        return $this->setTpl($path, $params);
    }

    protected function setTpl($path, $params = [])
    {
        try {
            /** @var \Twig\Environment $twig */
            $twig = $this->module->get('twig');

            $loader = new \Twig\Loader\FilesystemLoader($this->module->getLocalPath() . 'views/twig/');
            $loader->addPath($this->module->getLocalPath() . 'views/twig/', 'twig');
            $loader->addPath(_PS_MODULE_DIR_, 'Modules');
            $twig->setLoader($loader);

            return $twig->render($path, $params);
        } catch (\Exception $e) {
            throw $e;
            // return '<div class="alert alert-danger">Error rendering template: ' . $e->getMessage() . '</div>';
        }
    }

    protected function getSettingsPageTplVars()
    {
        $cronControllerUrl = $this->context->link->getModuleLink($this->module->name, 'Cron');
        $adminController = $this->context->link->getAdminLink($this->controller_name, true);
        $configSettings = $this->configValues->getConfigValues();
        $settings = [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $adminController,
            'cronControllerUrl' => $cronControllerUrl,
            'cronImportProducts' => _PS_ROOT_DIR_ . 'modules/mpapityres/Cron/full-import-tyres.php',
            'manufacturers' => $this->getManufacturers(),
            'totalManufacturers' => $this->getTotalManufacturers(),
            'totalTyres' => $this->getTotalTyres(),
            'totalSuppliers' => $this->getTotalSuppliers(),
            'filters' => $this->getFilters(),
            'categoryTree' => $this->getCategoryTreeHtml(),
            'taxRulesGroups' => $this->configValues->getTaxRulesGroups(),
            'configValues' => $this->configValues,
        ];

        return array_merge($settings, $configSettings);
    }

    public function getCategoryTreeHtml()
    {
        $id_lang = (int) $this->context->language->id;
        $root_category = Category::getRootCategory($id_lang);
        $id_default_category = (int) $this->configValues->getCategoryIdByName();
        $helperTree = new HelperTreeCategories(
            'categories',
            'Elenco Categorie',
            $root_category->id,
            $this->id_lang,
            true
        );

        $helperTree->setUseSearch(true);
        $helperTree->setUseCheckBox(false);
        $helperTree->setFullTree(false);
        $helperTree->setSelectedCategories([$id_default_category]);
        $helperTree = $helperTree->render();

        return $helperTree;
    }

    protected function getMainPageTplVars()
    {
        $cronControllerUrl = $this->context->link->getModuleLink($this->module->name, 'Cron');
        $adminController = $this->context->link->getAdminLink($this->controller_name, true);

        return [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $adminController,
            'cronControllerUrl' => $cronControllerUrl,
            'downloadCatalogActionUrl' => $this->context->link->getModuleLink($this->module->name, 'DownloadCatalog'),
            'importCatalogActionUrl' => $this->context->link->getModuleLink($this->module->name, 'ImportCatalog'),
            'reloadImagesActionUrl' => $this->context->link->getModuleLink($this->module->name, 'ReloadImages'),
            'deleteProductsActionUrl' => $this->context->link->getModuleLink($this->module->name, 'DeleteProducts'),
            'taxRulesGroups' => $this->configValues->getTaxRulesGroups(),
            'idTaxRulesGroup' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP,
            'idTaxRulesGroupPfu' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP_PFU,
            'csvEndpointUrl' => $this->configValues->getCsvEndpointUrl(),
            'pfuList' => $this->getPfuList(),
            'summary' => $this->getSummary(),
        ];
    }

    protected function getPfuPageTplVars()
    {
        $cronControllerUrl = $this->context->link->getModuleLink($this->module->name, 'Cron');
        $adminController = $this->context->link->getAdminLink($this->controller_name, true);

        return [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $adminController,
            'cronControllerUrl' => $cronControllerUrl,
            'taxRulesGroups' => $this->configValues->getTaxRulesGroups(),
            'idTaxRulesGroup' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP,
            'idTaxRulesGroupPfu' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP_PFU,
            'csvEndpointUrl' => $this->configValues->getCsvEndpointUrl(),
            'profiles' => $this->getProfiles(),
            'seasons' => $this->getSeasons(),
            'summary' => $this->getSummary(),
            'pfuList' => $this->getPfuList(),
            'treeCategories' => $this->getCategoryTreeHtml(),
            'downloadCatalogActionUrl' => $adminController . '&action=downloadCatalogAction',
            'importCatalogActionUrl' => $adminController . '&action=importCatalogAction',
            'reloadImagesActionUrl' => $adminController . '&action=reloadImagesAction',
            'deleteProductsActionUrl' => $adminController . '&action=deleteProductsAction',
            'cronToken' => md5('MpFrontLogin'),
        ];
    }

    protected function getPfuList()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('p.id_product, p.reference, p.price, pl.name, count(pfu.id_product) as products')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product and pl.id_lang=' . (int) $id_lang)
            ->leftJoin('product_pfu', 'pfu', 'p.id_product = pfu.id_pfu')
            ->where("pl.name like 'PFU%'")
            ->groupBy('p.id_product')
            ->orderBy('pl.name');

        return $db->executeS($sql);
    }

    protected function getIdFeatureByName($name)
    {
        $name = pSQL($name);
        $id_lang = (int) Context::getContext()->language->id;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('id_feature')
            ->from('feature_lang')
            ->where("name ='{$name}'")
            ->where("id_lang={$id_lang}");

        return (int) $db->getValue($sql);
    }

    protected function getProfiles()
    {
        $id_feature = 6;
        $id_lang = (int) Context::getContext()->language->id;
        $profiles = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature);

        return $profiles;
    }

    protected function getSeasons()
    {
        $id_feature = $this->getIdFeatureByName('USO');

        if (!$id_feature) {
            return [];
        }

        $id_lang = (int) Context::getContext()->language->id;
        $seasons = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature);

        return $seasons;
    }

    protected function getSummary()
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $pfx = _DB_PREFIX_;
        // Prodotti totali
        $queryTotalProducts = "SELECT COUNT(*) as product FROM {$pfx}product";
        $totalProducts = $db->getValue($queryTotalProducts);
        // Prodotti disattivati
        $queryDisabledProducts = "SELECT COUNT(*) as disabled FROM {$pfx}product WHERE active = 0";
        $disabledProducts = $db->getValue($queryDisabledProducts);
        // Product Tyre
        $queryProductTyre = "SELECT COUNT(*) as product_tyre FROM {$pfx}product_tyre";
        $productTyre = $db->getValue($queryProductTyre);
        // Produttori
        $queryManufacturers = "SELECT COUNT(*) as manufacturer FROM {$pfx}manufacturer";
        $manufacturers = $db->getValue($queryManufacturers);
        // Fornitori
        $features = \Feature::getFeatures($id_lang);
        // Ultimo fetch download
        $lastDateDownload = $this->configValues->MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE;
        // Ultimo fetch import
        $lastDateImport = $this->configValues->MPAPITYRES_CRON_IMPORT_UPDATED_DATE;
        // Ultimo fetch reload images
        $lastDateReloadImages = $this->configValues->MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED_DATE;

        return [
            'products_count' => $totalProducts,
            'products_disabled' => $disabledProducts,
            'products_tyre_count' => $productTyre,
            'manufacturers_count' => $manufacturers,
            'features' => $features,
            'last_fetch_download' => $lastDateDownload,
            'last_fetch_import' => $lastDateImport,
            'last_fetch_reload_images' => $lastDateReloadImages,
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
        return $this->configValues->getCategoryIdByName();
    }

    private function getIdTaxRulesGroup()
    {
        return $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP;
    }

    public function postProcess()
    {
        $params = Tools::getAllValues();
        $message = '';

        if (isset($params['form-action']) && $params['form-action'] == 'saveSettings') {
            $this->saveSettingsApi($params);
            $this->saveSettingFilters($params);
            $loadPriceCount = $this->saveSettingProducts($params);

            $message = '
                <div class="alert alert-success">
                    <p>Impostazioni salvate</p>
                </div>
            ';

            if ($loadPriceCount) {
                $message .= "
                    <div class=\"alert alert-success\">
                        <p>Sono stati aggiornati {$loadPriceCount} prodotti.</p>
                        <p>Riapplicare i prezzi ai prodotti.</p>
                    </div>
                ";
            } else {
                $message .= '
                    <div class="alert alert-success">
                        <p>Nessun prodotto aggiornato.</p>
                    </div>
                ';
            }
        }

        $this->message = $message;

        return true;
    }

    private function saveSettingsApi($params)
    {
        $host = $params['MPAPITYRES_API_ENDPOINT'];
        $token = $params['MPAPITYRES_API_TOKEN'];
        $pause = $params['MPAPITYRES_CRON_TIME_BETWEEN_UPDATES'];

        $config = ConfigValues::getInstance();

        $config->setValue('MPAPITYRES_API_ENDPOINT', $host);
        $config->setValue('MPAPITYRES_API_TOKEN', $token);
        $config->setValue('MPAPITYRES_CRON_TIME_BETWEEN_UPDATES', $pause);

        return true;
    }

    private function saveSettingProducts($params)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $id_category = $params['categoryBox'];
        $category = new \Category($id_category, $id_lang);
        $category_name = (string) $category->name;
        $id_tax_rules_group = $params['id_tax_rules_group'];
        $ricaricoC1 = $params['MPAPITYRES_RICARICO_C1'];
        $ricaricoC2 = $params['MPAPITYRES_RICARICO_C2'];
        $ricaricoC3 = $params['MPAPITYRES_RICARICO_C3'];
        $ricaricoDefault = $params['MPAPITYRES_RICARICO_DEFAULT'];

        $config = ConfigValues::getInstance();

        $config->setValue('MPAPITYRES_DEFAULT_CATEGORY', $category_name);
        $config->setValue('MPAPITYRES_ID_TAX_RULES_GROUP', $id_tax_rules_group);
        $config->setValue('MPAPITYRES_RICARICO_C1', $ricaricoC1);
        $config->setValue('MPAPITYRES_RICARICO_C2', $ricaricoC2);
        $config->setValue('MPAPITYRES_RICARICO_C3', $ricaricoC3);
        $config->setValue('MPAPITYRES_RICARICO_DEFAULT', $ricaricoDefault);

        $loadPriceCount = LoadPriceHelper::updateLoadPrices();

        return $loadPriceCount;
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

    public function getDiffPriceProductsAction()
    {
        $list = ModelProductTyre::getPriceListDiff();

        $this->response([
            'rows' => $list,
            'total' => count($list),
            'totalNotFiltered' => count($list),
        ], 200);
    }

    public function reloadPricesAction()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $rows = json_decode(Tools::getValue('rows'), true);

        $message = LoadPriceHelper::reloadPrices($rows, $id_lang);

        $this->response([
            'success' => true,
            'alert' => "
                <div class=\"alert alert-success\" role=\"alert\">
\t\t\t\t\t<strong>{$message}</strong> 
\t\t\t\t</div>
            ",
            'message' => $message,
        ]);
    }
}
