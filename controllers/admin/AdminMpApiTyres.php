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
use MpSoft\MpApiTyres\Helpers\GetCategoryIdByName;
use MpSoft\MpApiTyres\Helpers\GetTwigEnvironment;
use MpSoft\MpApiTyres\Helpers\LoadPriceHelper;
use MpSoft\MpApiTyres\Models\ModelProductCsvTyre;
use MpSoft\MpApiTyres\Models\ModelProductPfu;
use MpSoft\MpApiTyres\Models\ModelProductPriceReload;
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
        if (Tools::isSubmit('action') && Tools::isSubmit('ajax')) {
            $action = 'ajaxProcess' . Tools::ucFirst(Tools::getValue('action'));

            if (method_exists($this, $action)) {
                $this->response($this->$action());
            }
        }
    }

    public function response($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);

        echo json_encode($data);

        exit;
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

        switch ($action) {
            case 'dashboard':
                $this->content = $this->showPageDashboard();
                break;
            case 'showSettings':
                $this->content = $this->message . $this->showPageSettings();
                break;
            case 'showCsv':
                $this->content = $this->message . $this->showPageCsv();
                break;
            case 'showImport':
                $this->content = $this->message . $this->showPageImport();
                break;
            case 'showPfu':
                $this->content = $this->message . $this->showPagePfu();
                break;
            default:
                $this->content = $this->showPageDashboard();
        }

        return parent::initContent();
    }

    public function ajaxProcessSavePfuIdTaxRulesGroup()
    {
        $idTaxRuleGroup = (int) Tools::getValue('idTaxRulesGroup');

        Configuration::updateValue('MPAPITYRES_PFU_ID_TAX_RULES_GROUP', $idTaxRuleGroup);

        $this->message = $this->module->l('Impostazioni salvate con successo');

        return [
            'success' => 1,
            'message' => $this->message,
        ];
    }

    public function ajaxProcessSaveProductSettings()
    {
        $showPriceTax = Tools::getValue('showPriceTax');
        $delayDays = Tools::getValue('delayDays');

        Configuration::updateValue('MPAPITYRES_SHOW_PRICE_TAX', $showPriceTax);
        Configuration::updateValue('MPAPITYRES_DELAY_DAYS', $delayDays);

        $this->message = $this->module->l('Impostazioni salvate con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveAddress()
    {
        $deliveryAddress = [
            'name' => pSQL(Tools::getValue('name')),
            'address' => pSQL(Tools::getValue('address')),
            'postcode' => pSQL(Tools::getValue('postcode')),
            'city' => pSQL(Tools::getValue('city')),
            'state' => pSQL(Tools::getValue('state')),
            'country' => pSQL(Tools::getValue('country')),
            'phone' => pSQL(Tools::getValue('phone')),
        ];

        Configuration::updateValue('MPAPITYRES_ADDRESS', json_encode($deliveryAddress));

        $this->message = $this->module->l('Indirizzo salvato con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveApiSettings()
    {
        $hostApi = Tools::getValue('MPAPITYRES_API_ENDPOINT');
        $tokenApi = Tools::getValue('MPAPITYRES_API_TOKEN');

        Configuration::updateValue('MPAPITYRES_API_ENDPOINT', $hostApi);
        Configuration::updateValue('MPAPITYRES_API_TOKEN', $tokenApi);

        $this->message = $this->module->l('Impostazioni salvate con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveAccount()
    {
        $accountId = Tools::getValue('accountId');
        $password = Tools::getValue('password');
        $csvToken = Tools::getValue('csvToken');

        Configuration::updateValue('MPAPITYRES_ACCOUNT_ID', $accountId);
        Configuration::updateValue('MPAPITYRES_PASSWORD', $password);
        Configuration::updateValue('MPAPITYRES_CSV_TOKEN', $csvToken);

        $this->message = $this->module->l('Impostazioni salvate con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveSearchFilters()
    {
        $filter_0 = explode(',', Tools::getValue('filter-0')[0]);
        $filter_1 = explode(',', Tools::getValue('filter-1')[0]);
        $filter_2 = explode(',', Tools::getValue('filter-2'));
        $filter_4 = explode(',', Tools::getValue('filter-4'));
        $filter_5 = explode(',', Tools::getValue('filter-5')[0]);
        $filter_6 = explode(',', Tools::getValue('filter-6'));

        Configuration::updateValue('MPAPITYRES_FILTER_0', json_encode($filter_0));
        Configuration::updateValue('MPAPITYRES_FILTER_1', json_encode($filter_1));
        Configuration::updateValue('MPAPITYRES_FILTER_2', json_encode($filter_2));
        Configuration::updateValue('MPAPITYRES_FILTER_4', json_encode($filter_4));
        Configuration::updateValue('MPAPITYRES_FILTER_5', json_encode($filter_5));
        Configuration::updateValue('MPAPITYRES_FILTER_6', json_encode($filter_6));

        $this->message = $this->module->l('Filtri salvati con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveImportParameters()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $id_category = (int) Tools::getValue('categoryBox');
        $id_tax_rules_group = (int) Tools::getValue('id_tax_rules_group');
        $ricarico_prezzo_perc = (float) Tools::getValue('ricarico-prezzo-perc');
        $ricarico_prezzo_eur = (float) Tools::getValue('ricarico-prezzo-amount');

        if ($id_category == 0) {
            $default_category = 'HOME';
        } else {
            $default_category = (new Category($id_category, $id_lang))->name;
        }

        Configuration::updateValue('MPAPITYRES_DEFAULT_CATEGORY', $default_category);
        Configuration::updateValue('MPAPITYRES_ID_TAX_RULES_GROUP', $id_tax_rules_group);
        Configuration::updateValue('MPAPITYRES_RELOAD_PERCENTAGE', $ricarico_prezzo_perc);
        Configuration::updateValue('MPAPITYRES_RELOAD_AMOUNT', $ricarico_prezzo_eur);

        $this->message = $this->module->l('Parametri salvati con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessSaveCsvAccount()
    {
        $account_id = Tools::getValue('account_id');
        $password = Tools::getValue('password');
        $csv_token = Tools::getValue('csv_token');

        Configuration::updateValue('MPAPITYRES_ACCOUNT_ID', $account_id);
        Configuration::updateValue('MPAPITYRES_PASSWORD', $password);
        Configuration::updateValue('MPAPITYRES_CSV_TOKEN', $csv_token);

        $this->message = $this->module->l('Account salvato con successo');

        return [
            'message' => $this->message,
            'success' => 1,
        ];
    }

    public function ajaxProcessApplyFilters()
    {
        $filter_ms = Tools::isSubmit('filter_ms') ? 1 : 0;
        $filter_ms_mandatory = Tools::isSubmit('filter_ms_mandatory') ? 1 : 0;
        $filter_runflat = Tools::isSubmit('filter_runflat') ? 1 : 0;
        $filter_runflat_mandatory = Tools::isSubmit('filter_runflat_mandatory') ? 1 : 0;
        $filter_da_decke = Tools::isSubmit('filter_da_decke') ? 1 : 0;
        $filter_da_decke_mandatory = Tools::isSubmit('filter_da_decke_mandatory') ? 1 : 0;
        $filter_demo = Tools::isSubmit('filter_demo') ? 1 : 0;
        $filter_demo_mandatory = Tools::isSubmit('filter_demo_mandatory') ? 1 : 0;
        $filter_dot = Tools::isSubmit('filter_dot') ? 1 : 0;
        $filter_dot_mandatory = Tools::isSubmit('filter_dot_mandatory') ? 1 : 0;
        $filter_3pmfs = Tools::isSubmit('filter_3pmfs') ? 1 : 0;
        $filter_3pmfs_mandatory = Tools::isSubmit('filter_3pmfs_mandatory') ? 1 : 0;
        $filter_quantity_min = (int) Tools::getValue('filter_quantity_min');
        $filter_quantity_min_mandatory = Tools::isSubmit('filter_quantity_min_mandatory') ? 1 : 0;
        $load_amount = (float) Tools::getValue('load_amount');
        $load_percentage = (float) Tools::getValue('load_percentage');
        $delivery_delay_days = (int) \Tools::getValue('delivery_delay_days');
        $id_tax_rules_group = (int) Tools::getValue('id_tax_rules_group');

        // Salva i filtri in configurazione o in sessione
        Configuration::updateValue('MPAPITYRES_FILTER_MS', $filter_ms);
        Configuration::updateValue('MPAPITYRES_FILTER_MS_MANDATORY', $filter_ms_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_RUNFLAT', $filter_runflat);
        Configuration::updateValue('MPAPITYRES_FILTER_RUNFLAT_MANDATORY', $filter_runflat_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_DA_DECKE', $filter_da_decke);
        Configuration::updateValue('MPAPITYRES_FILTER_DA_DECKE_MANDATORY', $filter_da_decke_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_DEMO', $filter_demo);
        Configuration::updateValue('MPAPITYRES_FILTER_DEMO_MANDATORY', $filter_demo_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_DOT', $filter_dot);
        Configuration::updateValue('MPAPITYRES_FILTER_DOT_MANDATORY', $filter_dot_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_3PMFS', $filter_3pmfs);
        Configuration::updateValue('MPAPITYRES_FILTER_3PMFS_MANDATORY', $filter_3pmfs_mandatory);
        Configuration::updateValue('MPAPITYRES_FILTER_QUANTITY_MIN', $filter_quantity_min);
        Configuration::updateValue('MPAPITYRES_FILTER_QUANTITY_MIN_MANDATORY', $filter_quantity_min_mandatory);
        Configuration::updateValue('MPAPITYRES_LOAD_AMOUNT', $load_amount);
        Configuration::updateValue('MPAPITYRES_LOAD_PERCENTAGE', $load_percentage);
        Configuration::updateValue('MPAPITYRES_DELIVERY_DELAY_DAYS', $delivery_delay_days);
        Configuration::updateValue('MPAPITYRES_TAX_RULES_GROUP_ID', $id_tax_rules_group);

        return [
            'message' => $this->module->l('Filtri applicati con successo'),
            'totalProducts' => ModelProductCsvTyre::countProducts(),
            'totalFiltered' => ModelProductCsvTyre::countProductsFiltered(),
            'success' => 1,
        ];
    }

    public function ajaxProcessApplyPfuAssociation()
    {
        $idPfu = (int) Tools::getValue('idPfu');
        $idProducts = Tools::getValue('idProducts');
        $idProduct = (int) Tools::getValue('idProduct');

        if ($idPfu == 0) {
            return [
                'ok' => false,
                'error' => $this->module->l('Id pfu non valido'),
            ];
        }

        if (is_array($idProducts) && !empty($idProducts)) {
            $ids = array_values(array_filter(array_map('intval', $idProducts)));
            if (empty($ids)) {
                return [
                    'ok' => false,
                    'error' => $this->module->l('Nessun prodotto selezionato'),
                ];
            }

            $result = ModelProductTyre::applyPfuAssociationBulk($ids, $idPfu);

            return [
                'ok' => (bool) $result,
                'message' => $this->module->l('Associazione salvata con successo'),
            ];
        }

        if ($idProduct == 0) {
            return [
                'ok' => false,
                'error' => $this->module->l('Id prodotto non valido'),
            ];
        }

        $result = ModelProductTyre::applyPfuAssociation($idProduct, $idPfu);

        return [
            'ok' => (bool) $result,
            'message' => $this->module->l('Associazione salvata con successo'),
        ];
    }

    public function ajaxProcessAssociateProductsToCategory()
    {
        $categoryId = (int) Tools::getValue('id_category');

        if ($categoryId > 0) {
            \Configuration::updateValue('MPAPITYRES_CATEGORY_ID_ASSOCIATED', $categoryId);
            return [
                'categoryId' => $categoryId,
                'success' => 1,
                'message' => $this->module->l('Categoria associata salvata con successo')
            ];
        }
        return [
            'success' => 0,
            'message' => $this->module->l('Categoria non valida')
        ];
    }

    public function ajaxProcessDownloadCsv()
    {
        $account_id = \Configuration::get('MPAPITYRES_ACCOUNT_ID');
        $csv_token = \Configuration::get('MPAPITYRES_CSV_TOKEN');

        $url = "https://tyre24.alzura.com//it/it/export/download-via-token/token/{$csv_token}/accountId/{$account_id}/t/1/c/35/";

        return ModelProductCsvTyre::downloadCsv($url);
    }

    public function ajaxProcessGetPfuAssociations()
    {
        return ModelProductTyre::getPfuAssociations();
    }

    public function ajaxProcessGetPriceReload()
    {
        return ModelProductPriceReload::getPriceReload();
    }

    public function ajaxProcessSavePriceReload()
    {
        $id = (int) Tools::getValue('price_load_id');
        $price_min = (float) Tools::getValue('price_min');
        $price_max = (float) Tools::getValue('price_max');
        $price_reload_amount = (float) Tools::getValue('price_reload_amount');
        $price_reload_perc = (float) Tools::getValue('price_reload_perc');

        if ($price_min < 0) {
            $price_min = 0;
        }

        if ($price_max < 0) {
            $price_max = 0;
        }

        if ($price_reload_amount < 0) {
            $price_reload_amount = 0;
        }

        if ($price_reload_perc < 0) {
            $price_reload_perc = 0;
        }

        if ($price_max == 0) {
            return [
                'ok' => false,
                'error' => 'Valore PREZZO MAX non corretto'
            ];
        }

        $modelPriceReload = new ModelProductPriceReload($id);
        $modelPriceReload->price_min = $price_min;
        $modelPriceReload->price_max = $price_max;
        $modelPriceReload->reload_amount = $price_reload_amount;
        $modelPriceReload->reload_perc = $price_reload_perc;

        $result = $modelPriceReload->updatePrice();

        return [
            'ok' => $result,
            'message' => 'Prezzo ricarico salvato con successo',
            'id' => (int) $modelPriceReload->id,
        ];
    }

    public function ajaxProcessDeletePriceReload()
    {
        $id = (int) Tools::getValue('id_price_reload');
        $model = new ModelProductPriceReload(($id));

        if (\Validate::isLoadedObject($model)) {
            $result = $model->delete();
            return [
                'ok' => $result,
                'message' => 'Prezzo ricarico eliminato con successo',
            ];
        }

        return [
            'ok' => false,
            'error' => 'Prezzo ricarico non trovato'
        ];
    }

    public function ajaxProcessEditPriceReload()
    {
        $id = (int) Tools::getValue('id_price_reload');
        $modelPriceReload = new ModelProductPriceReload($id);
        if (\Validate::isLoadedObject($modelPriceReload)) {
            return [
                'ok' => true,
                'data' => $modelPriceReload->getFields(),
                'message' => 'Prezzo ricarico pronto per essere modificato.'
            ];
        }
        return [
            'ok' => false,
            'error' => 'Prezzo ricarico non trovato'
        ];
    }

    public function ajaxProcessGeneratePfuFromCsv()
    {
        $pfuList = ModelProductTyre::getPfuTableList();
        ModelProductTyre::createPfuList($pfuList);

        return [
            'success' => 1,
            'message' => $this->module->l('PFU generati con successo'),
            'tbody' => $this->renderPfuList(),
        ];
    }

    public function ajaxProcessImportCsv()
    {
        $start = microtime(true);

        $path = _PS_ROOT_DIR_ . '/download/csv/';
        $csv = \Configuration::get('MPAPITYRES_ACCOUNT_ID') . '_it.csv';
        // cerco il file .csv
        $file = $path . $csv;
        if (file_exists($file)) {
            // Estraggo i dati dal CSV e li inserisco nella tabella
            $result = ModelProductCsvTyre::insertData($file, '|');
        }

        $end = microtime(true);
        $this->message = $this->module->l('Importazione completata in ' . ($end - $start) . ' secondi');

        return [
            'message' => $this->message,
            'success' => 1,
            'errors' => json_encode($result['errors']),
            'inserted' => (int) $result['inserted'],
        ];
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
        $action = Tools::getValue('action', 'dashboard');

        $this->page_header_toolbar_btn['dashboard'] = [
            'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'dashboard']),
            'desc' => $this->trans('Dashboard'),
            'icon' => 'icon-home',
            'class' => 'btn-show-settings ' . ($action == 'dashboard' ? 'active' : ''),
        ];

        /*
         * $this->page_header_toolbar_btn['show-settings'] = [
         *     'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showSettings']),
         *     'desc' => $this->trans('Impostazioni'),
         *     'icon' => 'icon-cogs',
         *     'class' => 'btn-show-settings ' . ($action == 'showSettings' ? 'active' : ''),
         * ];
         */

        $this->page_header_toolbar_btn['show-csv'] = [
            'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showCsv']),
            'desc' => $this->trans('Importa CSV'),
            'icon' => 'icon-download',
            'class' => 'btn-show-csv ' . ($action == 'showCsv' ? 'active' : ''),
        ];

        /*
         * $this->page_header_toolbar_btn['show-import'] = [
         *     'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showImport']),
         *     'desc' => $this->trans('Importazione'),
         *     'icon' => 'icon-download',
         *     'class' => 'btn-show-settings ' . ($action == 'showImport' ? 'active' : ''),
         * ];
         */

        $this->page_header_toolbar_btn['show-pfu'] = [
            'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'showPfu']),
            'desc' => $this->trans('Associa i PFU'),
            'icon' => 'icon-compress',
            'class' => 'btn-show-pfu ' . ($action == 'showPfu' ? 'active' : ''),
        ];

        unset($this->page_header_toolbar_btn['new']);
        unset($this->page_header_toolbar_btn['delete']);
    }

    protected function getToolbarBtn($page)
    {
        switch ($page) {
            case 'default':
            case 'settings':
                break;
            case 'pfu':
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

    protected function showPageDashboard()
    {
        $path = '@ModuleTwig/AdminController/dashboard.html.twig';
        $params = $this->getTplVarsDashboard();
        return $this->setTpl($path, $params);
    }

    protected function showPageCsv()
    {
        $path = '@ModuleTwig/AdminController/page.csv.html.twig';
        $params = $this->getTplVarsPageCsv();
        return $this->setTpl($path, $params);
    }

    protected function showPageSettings()
    {
        $path = '@ModuleTwig/AdminController/settings.html.twig';
        $params = $this->getTplVarsPageSettings();
        return $this->setTpl($path, $params);
    }

    protected function showPageImport()
    {
        $path = '@ModuleTwig/AdminController/import.html.twig';
        $params = $this->getTplVarsImportPage();
        return $this->setTpl($path, $params);
    }

    protected function showPagePfu()
    {
        $path = '@ModuleTwig/AdminController/page.pfu.html.twig';
        $params = $this->getTplVarsPagePfu();
        return $this->setTpl($path, $params);
    }

    protected function setTpl($path, $params = [])
    {
        try {
            /** @var GetTwigEnvironment $twig */
            $twig = new GetTwigEnvironment($this->module->name);
            $twig->load($path);

            return $twig->render($params);
        } catch (\Exception $e) {
            throw $e;
            // return '<div class="alert alert-danger">Error rendering template: ' . $e->getMessage() . '</div>';
        }
    }

    protected function getTplVarsImportPage()
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
            'csvEndpointUrl' => $this->configValues->getCsvEndpointUrl(),
            'show_price_tax' => \Configuration::get('MPAPITYRES_SHOW_PRICE_TAX'),
            'delay_days' => \Configuration::get('MPAPITYRES_DELIVERY_DELAY'),
            'pfuList' => $this->getPfuList(),
            'summary' => $this->getSummary(),
        ];

        return array_merge($settings, $configSettings);
    }

    public function getCategoryTreeHtml($categoryIdAssociated = 0)
    {
        $id_lang = (int) $this->context->language->id;
        $root_category = Category::getRootCategory($id_lang);
        if (!$categoryIdAssociated) {
            $id_default_category = (int) $this->configValues->getCategoryIdByName();
        } else {
            $id_default_category = $categoryIdAssociated;
        }

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

    protected function getTplVarsDashboard()
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
            'show_price_tax' => \Configuration::get('MPAPITYRES_SHOW_PRICE_TAX'),
            'delay_days' => \Configuration::get('MPAPITYRES_DELIVERY_DELAY'),
            'pfuList' => $this->getPfuList(),
            'summary' => $this->getSummary(),
        ];
    }

    protected function getTplVarsPageCsv()
    {
        $cronControllerUrl = $this->context->link->getModuleLink($this->module->name, 'Cron');
        $adminController = $this->context->link->getAdminLink($this->controller_name, true);
        $categoryIdAssociated = (int) \Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED');

        return [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $adminController,
            'cronControllerUrl' => $cronControllerUrl,
            'show_price_tax' => \Configuration::get('MPAPITYRES_SHOW_PRICE_TAX'),
            'delay_days' => \Configuration::get('MPAPITYRES_DELIVERY_DELAY'),
            'reload_price_amount' => \Configuration::get('MPAPITYRES_RELOAD_AMOUNT'),
            'reload_price_perc' => \Configuration::get('MPAPITYRES_RELOAD_PERCENTAGE'),
            'account_id' => \Configuration::get('MPAPITYRES_ACCOUNT_ID'),
            'password' => \Configuration::get('MPAPITYRES_PASSWORD'),
            'csv_token' => \Configuration::get('MPAPITYRES_CSV_TOKEN'),
            'check_table' => ModelProductCsvTyre::checkTableExists(),
            'filter_ms' => (int) \Configuration::get('MPAPITYRES_FILTER_MS'),
            'filter_ms_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_MS_MANDATORY'),
            'filter_runflat' => (int) \Configuration::get('MPAPITYRES_FILTER_RUNFLAT'),
            'filter_runflat_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_RUNFLAT_MANDATORY'),
            'filter_da_decke' => (int) \Configuration::get('MPAPITYRES_FILTER_DA_DECKE'),
            'filter_da_decke_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_DA_DECKE_MANDATORY'),
            'filter_demo' => (int) \Configuration::get('MPAPITYRES_FILTER_DEMO'),
            'filter_demo_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_DEMO_MANDATORY'),
            'filter_dot' => (int) \Configuration::get('MPAPITYRES_FILTER_DOT'),
            'filter_dot_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_DOT_MANDATORY'),
            'filter_3pmfs' => (int) \Configuration::get('MPAPITYRES_FILTER_3PMFS'),
            'filter_3pmfs_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_3PMFS_MANDATORY'),
            'filter_quantity_min' => (int) \Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN'),
            'filter_quantity_min_mandatory' => (int) \Configuration::get('MPAPITYRES_FILTER_QUANTITY_MIN_MANDATORY'),
            'load_amount' => (float) \Configuration::get('MPAPITYRES_LOAD_AMOUNT'),
            'load_percentage' => (float) \Configuration::get('MPAPITYRES_LOAD_PERCENTAGE'),
            'delivery_delay_days' => (int) \Configuration::get('MPAPITYRES_DELIVERY_DELAY_DAYS'),
            'cronDownloadCsv' => 'php ' . _PS_ROOT_DIR_ . '/modules/mpapityres/cron/full-import-csv.php',
            'categoryTree' => $this->getCategoryTreeHtml($categoryIdAssociated),
            'categoryIdAssociated' => (int) \Configuration::get('MPAPITYRES_CATEGORY_ID_ASSOCIATED'),
            'taxRulesGroups' => \TaxRulesGroup::getTaxRulesGroups(true),
            'id_tax_rules_group' => (int) \Configuration::get('MPAPITYRES_TAX_RULES_GROUP_ID'),
            'totalProducts' => ModelProductCsvTyre::countProducts(),
            'totalFiltered' => ModelProductCsvTyre::countProductsFiltered(),
            'deliveryAddress' => json_decode(Configuration::get('MPAPITYRES_ADDRESS'), true),
        ];
    }

    protected function getTplVarsPageSettings()
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
            'accountId' => \Configuration::get('MPAPITYRES_ACCOUNT_ID'),
            'password' => \Configuration::get('MPAPITYRES_PASSWORD'),
            'csvToken' => \Configuration::get('MPAPITYRES_CSV_TOKEN'),
            'taxRulesGroups' => $this->configValues->getTaxRulesGroups(),
            'idTaxRulesGroup' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP,
            'idTaxRulesGroupPfu' => $this->configValues->MPAPITYRES_ID_TAX_RULES_GROUP_PFU,
            'csvEndpointUrl' => $this->configValues->getCsvEndpointUrl(),
            'show_price_tax' => \Configuration::get('MPAPITYRES_SHOW_PRICE_TAX'),
            'delay_days' => \Configuration::get('MPAPITYRES_DELIVERY_DELAY'),
            'pfuList' => $this->getPfuList(),
            'summary' => $this->getSummary(),
            'manufacturers' => $this->getManufacturers(),
            'totalManufacturers' => $this->getTotalManufacturers(),
            'totalTyres' => $this->getTotalTyres(),
            'totalSuppliers' => $this->getTotalSuppliers(),
            'filters' => $this->getFilters(),
            'categoryTree' => $this->getCategoryTreeHtml(),
            'configValues' => $this->configValues,
            'cronImportProducts' => _PS_ROOT_DIR_ . 'modules/mpapityres/Cron/full-import-tyres.php',
            'defaultCategoryName' => Configuration::get('MPAPITYRES_DEFAULT_CATEGORY'),
            'defaultCategoryId' => GetCategoryIdByName::get(Configuration::get('MPAPITYRES_DEFAULT_CATEGORY')),
            'defaultTaxRulesGroup' => Configuration::get('MPAPITYRES_ID_TAX_RULES_GROUP'),
            'defaultReloadPerc' => Configuration::get('MPAPITYRES_RELOAD_PERCENTAGE'),
            'defaultReloadAmount' => Configuration::get('MPAPITYRES_RELOAD_AMOUNT'),
            'defaultShowPriceTax' => Configuration::get('MPAPITYRES_SHOW_PRICE_TAX'),
            'defaultDelayDays' => Configuration::get('MPAPITYRES_DELAY_DAYS'),
            'defaultFilter0' => json_decode(Configuration::get('MPAPITYRES_FILTER_0'), true),
            'defaultFilter1' => json_decode(Configuration::get('MPAPITYRES_FILTER_1'), true),
            'defaultFilter2' => json_decode(Configuration::get('MPAPITYRES_FILTER_2'), true),
            'defaultFilter4' => json_decode(Configuration::get('MPAPITYRES_FILTER_4'), true),
            'defaultFilter5' => json_decode(Configuration::get('MPAPITYRES_FILTER_5'), true),
            'defaultFilter6' => json_decode(Configuration::get('MPAPITYRES_FILTER_6'), true),
            'account_id' => Configuration::get('MPAPITYRES_ACCOUNT_ID'),
            'account_pwd' => Configuration::get('MPAPITYRES_ACCOUNT_PWD'),
            'token_tyres_api' => Configuration::get('MPAPITYRES_TOKEN_TYRES_API'),
        ];
    }

    protected function getTplVarsPagePfu()
    {
        $adminController = $this->context->link->getAdminLink($this->controller_name, true);
        $id_pfu_tax_rules_group = (int) (int) Configuration::get('MPAPITYRES_PFU_ID_TAX_RULES_GROUP');
        $pfu_tax_rate = ModelProductTyre::getTaxRate($id_pfu_tax_rules_group, 0);
        $pfu_csv = ModelProductTyre::getPfuTableList();

        $params = [
            'baseUrl' => $this->context->link->getBaseLink(),
            'adminControllerUrl' => $adminController,
            'id_pfu_tax_rules_group' => $id_pfu_tax_rules_group,
            'pfu_tax_rate' => $pfu_tax_rate,
            'id_tax_rules_group_list' => TaxRulesGroup::getTaxRulesGroups(true),
            'pfuCsv' => $pfu_csv,
            'pfuListOptions' => $this->getPfuListOptions(),
            'pfuListTbody' => $this->renderPfuList(),
            'totalPfuAssociated' => $this->getTotalPfuAssociated(),
            'totalProducts' => $this->getTotalProducts(),
        ];

        return $params;
    }

    protected function getTotalPfuAssociated()
    {
        $db = db::getInstance();
        $query = new DbQuery();
        $query
            ->select('count(id_product) as total')
            ->from('product_tyre')
            ->where('id_pfu_associated > 0');

        return $db->getValue($query);
    }

    protected function getProductsAssociated()
    {
        $db = db::getInstance();
        $query = new DbQuery();
        $query
            ->select('id_product, reference, count(id_product) as total')
            ->from('product_tyre')
            ->where('type_tyre = "pfu"')
            ->where('id_pfu_associated > 0')
            ->groupBy('id_product');

        return $db->getValue($query);
    }

    protected function getTotalProducts()
    {
        $db = db::getInstance();
        $query = new DbQuery();
        $query->select('count(id_product) as total');
        $query->from('product');

        return $db->getValue($query);
    }

    protected function getPfuList()
    {
        $pfuTables = ModelProductTyre::getPfuTableList();

        $id_lang = (int) Context::getContext()->language->id;
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('p.id_product, p.reference, p.price, pl.name, count(pfu.id_product) as products')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product and pl.id_lang=' . (int) $id_lang)
            ->innerJoin('product_tyre', 'pt', "p.id_product = pt.id_product and pt.type_tyre = 'pfu'")
            ->innerJoin('product_tyre', 'pfu', 'p.id_product = pfu.id_product')
            ->where("pl.name like 'PFU%'")
            ->groupBy('p.id_product')
            ->orderBy('p.id_product');

        return $db->executeS($sql);
    }

    protected function getPfuListOptions()
    {
        $pfuList = $this->getPfuList();
        $out = [];
        foreach ($pfuList as $pfu) {
            $out[$pfu['id_product']] = $pfu['name'];
        }

        return $out;
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

        if (Tools::isSubmit('submitCreateTableCsv')) {
            $this->createTableCsv();
            exit();
        }

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

    public function createTableCsv()
    {
        ModelProductCsvTyre::install();

        Tools::redirect($this->context->link->getAdminLink('AdminMpApiTyres', true, [], ['action', 'showCsv']));
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

    protected function renderPfuList()
    {
        $twig = new GetTwigEnvironment($this->module->name);
        $twig->load('@ModuleTwig/AdminController/partials/pfu.list.html.twig');

        $db = db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('p.id_product, p.reference, p.id_tax_rules_group, pfu.pfu_weight_min, pfu.pfu_weight_max, pfu.price')
            ->from('product_tyre', 'pfu')
            ->innerJoin('product', 'p', 'p.id_product=pfu.id_product')
            ->where("pfu.type_tyre = 'pfu'")
            ->orderBy('pfu.pfu_weight_min, pfu.pfu_weight_max');
        $results = $db->executeS($sql);

        $tax = [];
        if ($results) {
            foreach ($results as &$result) {
                $id_tax_rules_group = (int) $result['id_tax_rules_group'];
                if (!isset($tax[$id_tax_rules_group])) {
                    $tax[$id_tax_rules_group] = ModelProductTyre::getTaxRate($id_tax_rules_group, 0);
                }
                $result['tax_rate'] = $tax[$id_tax_rules_group];
                $result['associated'] = 0;  // TODO: Calcola i prodotti associati
            }
        }

        return $twig->render(['pfuList' => $results]);
    }
}
