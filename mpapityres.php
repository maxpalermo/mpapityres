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

use MpSoft\MpApiTyres\Helpers\DownloadAPI;
use MpSoft\MpApiTyres\Helpers\DownloadCsv;
use MpSoft\MpApiTyres\Helpers\LoadPriceHelper;
use MpSoft\MpApiTyres\Helpers\TwigHelper;
use MpSoft\MpApiTyres\Models\ModelProductCsvAlloy;
use MpSoft\MpApiTyres\Models\ModelProductCsvTyre;
use MpSoft\MpApiTyres\Models\ModelProductPriceReload;
use MpSoft\MpApiTyres\Models\ModelProductTyre;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DateTimeColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShopBundle\Form\Admin\Type\DateRangeType;

class MpApiTyres extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'mpapityres';
        $this->tab = 'administration';
        $this->version = '1.5.69';
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
            'displayAdminEndContent',
            'actionProductGridDefinitionModifier',
            'actionProductGridQueryBuilderModifier',
            'actionProductGridDataModifier',
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
            $tab->name[$language['id_lang']] = 'Tyre 24 API';
        }
        $tabAdd = $tab->add();

        $installTable =
            ModelProductTyre::install() &&
            ModelProductCsvTyre::install() &&
            ModelProductCsvAlloy::Install() &&
            ModelProductPriceReload::install();

        return $tabAdd && $installTable;
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
        $apiTyres14Token = Configuration::get('API_REST_TYRES14_TOKEN');
        $apiProductsToken = Configuration::get('API_REST_PRODUCTS_TOKEN');
        $apiAlloysToken = Configuration::get('API_REST_ALLOYS_TOKEN');
        $apiWearPartsToken = Configuration::get('API_REST_WEARPARTS_TOKEN');

        $filters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/filters.json');
        $sorters = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres/views/assets/js/Tyre/sorters.json');

        $twigHelper = new TwigHelper();
        return $twigHelper->renderView(
            '@Modules/mpapityres/views/twig/getContent.html.twig',
            [
                'baseUrl' => $this->context->link->getBaseLink(),
                'adminMpApiTyres' => $this->context->link->getAdminLink('AdminMpApiTyres'),
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

    public function hookActionProductGridDefinitionModifier($params)
    {
        $id_lang = (int) $this->context->language->id;
        $choices = [];
        /** @var PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition */
        $definition = $params['definition'];

        $columns = $definition->getColumns();

        // Aggiungi colonna Data
        $column = new DateTimeColumn('date_add');
        $column
            ->setName($this->l('Data creaz.'))
            ->setOptions([
                'field' => 'date_add',
                'format' => 'd/m/Y',  // Custom format
                'sortable' => true,
                'alignment' => 'center',
            ]);

        $columns->addAfter('position', $column);

        $definition->getFilters()->add(
            (new Filter('date_add', DateRangeType::class))
                ->setTypeOptions([
                    'required' => false,
                    'label' => $this->trans('Data creazione', [], 'Admin.Global'),
                    'placeholder' => $this->trans('Da/a', [], 'Admin.Global'),
                    'attr' => [
                        'placeholder' => $this->trans('gg/mm/aaaa', [], 'Admin.Global'),
                        // 'data-date-format' => 'DD/MM/YYYY',
                    ],
                    // 'date_format' => 'DD/MM/YYYY',  // Formato italiano
                    // 'csrf_protection' => false,  // Disabilita CSRF per questo campo
                ])
                ->setAssociatedColumn('date_add')
        );
    }

    public function hookActionProductGridQueryBuilderModifier(array $params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        /** @var \PrestaShop\PrestaShop\Core\Search\Filters\ProductFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        // Aggiungi il campo date_add dalla tabella product
        // Nota: la tabella principale è già 'p' (alias per ps_product)
        $searchQueryBuilder->addSelect('p.date_add');

        // Se vuoi anche formattarla in modo diverso
        $searchQueryBuilder->addSelect('DATE_FORMAT(p.date_add, "%d/%m/%Y %H:%i") as date_add_formatted');

        // Se hai bisogno di filtrare per data
        $this->applyDateFilters($searchQueryBuilder, $searchCriteria->getFilters());
    }

    private function applyDateFilters($queryBuilder, array $filters)
    {
        if (isset($filters['date_add']) && is_array($filters['date_add'])) {
            $dateFilter = $filters['date_add'];

            if (!empty($dateFilter['from'])) {
                $queryBuilder
                    ->andWhere('p.date_add >= :date_from')
                    ->setParameter('date_from', $dateFilter['from'] . ' 00:00:00');
            }

            if (!empty($dateFilter['to'])) {
                $queryBuilder
                    ->andWhere('p.date_add <= :date_to')
                    ->setParameter('date_to', $dateFilter['to'] . ' 23:59:59');
            }
        }
    }

    public function hookActionProductGridDataModifier($params)
    {
        // Nothing to do
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = Context::getContext()->controller;
        if ($controller instanceof AdminmodulesControllerCore) {
            $this->context->controller->addCSS($this->getLocalPath() . 'views/assets/css/style.css');
        }
    }

    public function hookDisplayAdminEndContent($params)
    {
        return $this->renderWidget('displayAdminEndContent', $params);
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

    public function getWidgetVariables($hookName, array $configuration)
    {
        return [];
    }

    public function renderWidget($hookName, array $configuration)
    {
        $controller = Tools::getValue('controller');
        $id_product = (int) Tools::getValue('id_product');

        $variables = $this->getWidgetVariables($hookName, $configuration);
        switch ($hookName) {
            case 'displayAdminEndContent':
                break;
        }
        return '';
    }

    public function cronReloadPrices()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        $diff = LoadPriceHelper::getDiffPrices();
        $message = LoadPriceHelper::reloadPrices($diff, Context::getContext()->language->id);

        return $message;
    }

    public function cronImportFromCsv($downloadCsv = true, $updateCsv = true, $updateCatalog = true)
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';
        $start = microtime(true);

        $account_id = \Configuration::get('MPAPITYRES_ACCOUNT_ID');

        if ($downloadCsv) {
            echo "Inizio download CSV\n";

            $csv_token = \Configuration::get('MPAPITYRES_CSV_TOKEN');

            $url = "https://tyre24.alzura.com//it/it/export/download-via-token/token/{$csv_token}/accountId/{$account_id}/t/1/c/35/";

            $message = ModelProductCsvTyre::downloadCsv($url);

            echo "File {$message['filename']} scaricato in {$message['time']}\n";
        } else {
            echo "Flag --download-csv rilevato: salto download CSV\n";
        }

        if ($updateCsv) {
            $path = _PS_ROOT_DIR_ . '/download/csv/';
            $csv = $path . $account_id . '_it.csv';
            $hash = md5(date('Y-m-d H:i:s'));

            echo "*** INIZIO PARSING CSV ***\n";
            echo "==========================\n";
            echo "FILE: {$csv}\n";

            $message = ModelProductCsvTyre::insertData($csv, '|', $hash);

            echo "Prodotti nuovi inseriti: {$message['inserted']}\n";
            echo "Prodotti esistenti saltati: {$message['skipped']}\n";
            echo "Prodotti aggiornati: {$message['updated']}\n";
            echo 'ERRORI: ' . implode("\n", $message['errors']) . "\n";

            $rows = (int) ModelProductCsvTyre::disableByHash($hash);
            echo "DISABILITATE {$rows} RIGHE DAL CSV";
        } else {
            echo "Flag --update-csv rilevato: salto parsing CSV\n";
        }

        if ($updateCatalog) {
            echo "\n*** INIZIO INSERIMENTO PRODOTTI IN CATALOGO ***\n";
            echo "=================================================\n";

            $message = ModelProductCsvTyre::updateCatalog();

            echo "Aggiornamento catalogo eseguito in {$message['time']}\n";
        } else {
            echo "Flag --update-catalog rilevato: salto aggiornamento catalogo\n";
        }

        echo "\nDisattivazione prodotti no presenti nel CSV\n";
        ModelProductCsvTyre::disableProducts();

        $end = microtime(true);
        echo "\nOPERAZIONE ESEGUITA IN " . $this->humanReadableSeconds($end - $start) . "\n";
    }

    public function cronDownloadCsv()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadCsv::getZipAndExtract();
    }

    public function cronParseCsv()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadCsv::parseCsvTotal();
    }

    public function cronDownloadAPI()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::doPostDownload();
    }

    public function cronParseAPI()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::parseCatalogTotal();
    }

    public function deactivateProductsByDate()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::deactivateProductsByDate();
    }

    public function enableMaintenanceMode()
    {
        // Abilitare la manutenzione
        Configuration::updateValue('PS_SHOP_ENABLE', '0');

        return true;
    }

    public function disableMaintenanceMode()
    {
        // Disabilitare la manutenzione
        Configuration::updateValue('PS_SHOP_ENABLE', '1');

        return true;
    }

    public function humanReadableSeconds($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
    }

    public function disablePriceZero()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::disablePriceZero();
    }

    public function activateProductsAPI()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::activateProductsAPI();
    }

    public function addPfu()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        DownloadAPI::addPfu();
    }

    public function cleanUp()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        ModelProductCsvTyre::cleanUp();
    }

    public function recalcWeight()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        ModelProductCsvTyre::recalcWeight();
    }

    public function deleteAllProducts()
    {
        require_once dirname(__FILE__) . '/vendor/autoload.php';

        $pfx = _DB_PREFIX_;
        $products = "SELECT id_product from {$pfx}product";
        $result = Db::getInstance()->executeS($products);
        $deleted = 0;
        foreach ($result as $product_id) {
            $product = new Product($product_id['id_product']);
            $product->delete();

            echo '.';
            $deleted++;
        }

        echo "\n\nELIMINATI {$deleted} prodotti\n\n";

        $pfx = _DB_PREFIX_;
        $tables = [
            'appagebuilder_extrapro',
            'appagebuilder_page',
            'blockwishlist_statistics',
            'cart_product',
            'category_product',
            'customer_thread',
            'customization',
            'customization_field',
            'feature_product',
            'image',
            'image_shop',
            'layered_price_index',
            'layered_product_attribute',
            'leofeature_compare_product',
            'leofeature_product_review',
            'leofeature_product_review_criterion_product',
            'leofeature_wishlist_product',
            'mailalert_customer_oos',
            'order_detail',
            'order_detail_tyre',
            'product',
            'product_attachment',
            'product_attribute',
            'product_attribute_shop',
            'product_carrier',
            'product_comment',
            'product_comment_criterion_product',
            'product_country_tax',
            'product_download',
            'product_group_reduction_cache',
            'product_lang',
            'product_sale',
            'product_shop',
            'product_supplier',
            'product_tag',
            'product_tyre',
            'search_index',
            'specific_price',
            'specific_price_priority',
            'stock',
            'stock_available',
            'supply_order_detail',
            'tvcmsmegamenu_item',
            'tvcmsmegamenu_item_shop',
            'tvcmsproduct_comment',
            'tvcmsproduct_comment_criterion_product',
            'url_video',
            'warehouse_product_location',
            'wishlist_product',
        ];

        foreach ($tables as $table) {
            $QUERY = "TRUNCATE TABLE {$pfx}$table";
            try {
                Db::getInstance()->execute($QUERY);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }

        echo "\nTabelle azzerate.\n";
    }

    public function deleteWrongPfu()
    {
        echo "\nEliminazione prodotti PFU non validi.";

        $pfx = _DB_PREFIX_;
        $QUERY = "
            SELECT id_product
            from {$pfx}product_tyre
            where type_tyre = 'pfu' and pfu_weight_max = 0
            ORDER BY id_product;
        ";
        $list = \Db::getInstance()->executeS($QUERY);
        $deleted = 0;
        foreach ($list as $item) {
            $product = new Product($item['id_product']);
            $product->delete();
            echo '.';
            $deleted++;
        }

        echo "\nOperazione eseguita. Prodotti eliminati: {$deleted}\n\n";
    }

    public static function getTablesByColumnName($columnName, $table)
    {
        $QUERY = "
            SELECT DISTINCT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME IN ('{$columnName}')
                AND TABLE_SCHEMA='{$table}'
            ORDER BY TABLE_NAME;
        ";

        $result = Db::getInstance()->executeS($QUERY);

        return $result;
    }
}
