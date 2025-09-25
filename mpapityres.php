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

if (!defined('_PS_VERSION_')) {
    exit;
}


use MpSoft\MpApiTyres\Helpers\ExecuteSqlHelper;
use MpSoft\MpApiTyres\Helpers\TwigHelper;
use MpSoft\MpApiTyres\Models\ProductTyreDistributorModel;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Dotenv\Dotenv;

class MpApiTyres extends Module
{
    public function __construct()
    {
        $this->name = 'mpapityres';
        $this->tab = 'administration';
        $this->version = '0.8.1';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Pneumatici Tyres24');
        $this->description = $this->l('Modulo base per la gestione pneumatici Tyre24 via API.');
        $this->confirmUninstall = $this->l('Sei sicuro di voler disinstallare questo modulo?');
    }

    public function install()
    {
        $hooks = [
            'actionAdminControllerSetMedia',
        ];

        if (!parent::install()) {
            return false;
        }
        $this->registerHook($hooks);

        $languages = Language::getLanguages();
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $idParent = $tabRepository->findOneIdByClassName('AdminCatalog');

        $tab = new Tab();
        $tab->id_parent = $idParent;
        $tab->class_name = 'AdminMpApiTyres';
        $tab->module = $this->name;
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = "Tyre 24 API";
        }
        $add = $tab->add();
        $executeSqlHelper = new ExecuteSqlHelper();
        $res1 = $executeSqlHelper->run('product_tyre_api');
        $res2 = $executeSqlHelper->run('product_tyre_image');
        $res3 = $executeSqlHelper->run('product_tyre_manufacturer');
        $res4 = ProductTyreDistributorModel::install();

        return $add && $res1 && $res2 && $res3 && $res4;
    }

    public function uninstall()
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $idTab = $tabRepository->findOneIdByClassName('AdminMpApiTyres');
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        $dotenv = new Dotenv();
        $dotenv->load(_PS_MODULE_DIR_ . 'mpapityres/.env');

        $apiTyres14Token = $_ENV['API_REST_TYRES14_TOKEN'];
        $apiProductsToken = $_ENV['API_REST_PRODUCTS_TOKEN'];
        $apiAlloysToken = $_ENV['API_REST_ALLOYS_TOKEN'];
        $apiWearPartsToken = $_ENV['API_REST_WEARPARTS_TOKEN'];

        $filters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/filters.json');
        $sorters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/sorters.json');

        $twigHelper = new TwigHelper();
        return $twigHelper->renderView(
            '@Modules/mpapityres/views/twig/getContent.html.twig',
            [
                'baseUrl' => $this->context->link->getBaseLink(),
                'filters' => $filters,
                'sorters' => $sorters,
                'apiTyres14Token' => $apiTyres14Token,
                'apiProductsToken' => $apiProductsToken,
                'apiAlloysToken' => $apiAlloysToken,
                'apiWearPartsToken' => $apiWearPartsToken,
                'cronGetCatalog' => $this->context->link->getModuleLink($this->name, 'Cron', ['action' => 'getCatalogAction', 'ajax' => 1], true),
                'cronImportCatalog' => $this->context->link->getModuleLink($this->name, 'Cron', ['action' => 'createPrestashopCatalogAction', 'ajax' => 1], true),
            ],
        );
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = Context::getContext()->controller;
        if ($controller instanceof AdminmodulesControllerCore) {
            $this->context->controller->addCSS($this->getLocalPath() . 'views/assets/css/style.css');
        }
    }

    public function getAdminLink($controller)
    {
        $router = SymfonyContainer::getInstance()->get('router');
        $routeName = 'mpapityres_admin_' . $controller;
        // Verifica se la route esiste
        $routeCollection = $router->getRouteCollection();
        if ($routeCollection->get($routeName)) {
            $url = $router->generate($routeName);
            return $url;
        } else {
            return null;
        }
    }
}
