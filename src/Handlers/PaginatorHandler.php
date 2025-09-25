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

class PaginatorHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    public function __construct()
    {
        parent::__construct();
    }

    public function render($totalRecords, $currentPage, $totalPages, $limit, $baseUrl)
    {
        // Calcolo intervallo centrato sulla pagina corrente, max 5 pagine
        $start = max($currentPage - 2, 1);
        $end = min($currentPage + 2, $totalPages);
        if (($end - $start) < 4) {
            if ($start == 1) {
                $end = min($start + 4, $totalPages);
            } elseif ($end == $totalPages) {
                $start = max($end - 4, 1);
            }
        }

        $paginator = [];
        // Pulsante "first"
        $paginator[] = [
            'enabled' => $currentPage > 1,
            'url' => "{$baseUrl}&page=1&limit={$limit}",
            'page' => 1,
            'limit' => $limit,
            'label' => 'first'
        ];
        // Pulsante "prev"
        $paginator[] = [
            'enabled' => $currentPage > 1,
            'url' => "{$baseUrl}&page=" . max($currentPage - 1, 1) . "&limit={$limit}",
            'page' => max($currentPage - 1, 1),
            'limit' => $limit,
            'label' => 'prev'
        ];
        // Pulsanti numerici
        for ($i = $start; $i <= $end; $i++) {
            $paginator[] = [
                'enabled' => $i != $currentPage,
                'url' => "{$baseUrl}&page={$i}&limit={$limit}",
                'page' => $i,
                'limit' => $limit,
                'label' => (string) $i
            ];
        }
        // Pulsante "next"
        $paginator[] = [
            'enabled' => $currentPage < $totalPages,
            'url' => "{$baseUrl}&page=" . min($currentPage + 1, $totalPages) . "&limit={$limit}",
            'page' => min($currentPage + 1, $totalPages),
            'limit' => $limit,
            'label' => 'next'
        ];
        // Pulsante "last"
        $paginator[] = [
            'enabled' => $currentPage < $totalPages,
            'url' => "{$baseUrl}&page={$totalPages}&limit={$limit}",
            'page' => $totalPages,
            'limit' => $limit,
            'label' => 'last'
        ];

        $paginatorVars = [
            'total_records' => $totalRecords,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'limit' => $limit,
            'paginator' => $paginator,
        ];

        return $this->container->get('twig')->render('@Modules/mpapityres/views/twig/AdminMpApiTyres/page/paginator.html.twig', $paginatorVars);
    }
}
