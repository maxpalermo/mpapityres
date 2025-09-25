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

class DashboardController extends FrameworkBundleAdminController
{
    public function index()
    {
        $html = $this->renderView(
            '@Modules/mpapityres/views/twig/AdminMpApiTyres/tabs/dashboard.html.twig',
            $this->getDemoData()
        );

        return $this->json($html);
    }

    protected function getDemoData()
    {
        // Lista dettagliata per eventuali tabelle/card
        $category_list = [
            [
                'name' => 'Estivi',
                'qty' => rand(1, 500),
                'image' => 'https://via.placeholder.com/150',
            ],
            [
                'name' => 'Invernali',
                'qty' => rand(1, 500),
                'image' => 'https://via.placeholder.com/150',
            ],
            [
                'name' => '4 Stagioni',
                'qty' => rand(1, 500),
                'image' => 'https://via.placeholder.com/150',
            ],
        ];
        $category_labels = array_column($category_list, 'name');
        $category_data = array_column($category_list, 'qty');

        $dashboardVars = [
            // Statistiche generali
            'total_products' => rand(1, 1000),
            'total_orders' => rand(1, 1000),
            'total_customers' => rand(1, 1000),
            'total_sales' => rand(1, 1000),
            'total_categories' => rand(1, 100),
            'total_quantity' => rand(1, 10000),
            'latest_products' => [],
            'top_selling_products' => [
                [
                    'name' => 'Pirelli Cinturato P7',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                    'category' => 'Estivi',
                ],
                [
                    'name' => 'Michelin Pilot Sport 4',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                    'category' => 'Estivi',
                ],
                [
                    'name' => 'Continental EcoContact 6',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                    'category' => 'Estivi',
                ],
            ],

            // Lista dettagliata per card/tabelle
            'category_list' => $category_list,

            // Dati per il grafico a torta (Chart.js)
            'category_distribution' => [
                'labels' => $category_labels,
                'data' => $category_data,
            ],

            // Migliori prodotti venduti
            'best_selling' => [
                [
                    'name' => 'Pirelli Cinturato P7',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                ],
                [
                    'name' => 'Michelin Pilot Sport 4',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                ],
                [
                    'name' => 'Continental EcoContact 6',
                    'qty' => rand(1, 500),
                    'revenue' => rand(100, 100000) / 100,
                    'image' => 'https://via.placeholder.com/150',
                    'sales' => rand(1, 1000),
                ],
            ],

            // Dati per il grafico "Andamento vendite prodotti" (ultimi 6 mesi)
            'sales_trend' => [
                'labels' => ['Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug'],
                'data' => [rand(1, 500), rand(1, 500), rand(1, 500), rand(1, 500), rand(1, 500), rand(1, 500)],
            ],

            // Dati per il grafico "Ripartizione prodotti per categoria" (Chart.js, compatibile)
            'category_pie' => [
                'labels' => $category_labels,
                'data' => $category_data,
                'backgroundColor' => ['#2196f3', '#4caf50', '#ff9800'],
            ],

            // Esempio di altre metriche (opzionale)
            'last_order_date' => '2025-07-25',
            'last_customer' => 'Mario Rossi',
        ];

        return $dashboardVars;
    }
}
