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

use MpSoft\MpApiTyres\Helpers\CurlCatalogDownload;
use MpSoft\MpApiTyres\Traits\HumanTimingTrait;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class DownloadCatalogCsvController extends FrameworkBundleAdminController
{
    use HumanTimingTrait;

    private $errors = [];

    public function index(Request $request)
    {
        $action = $request->request->get('action') ?? null;
        if ($action) {
            if (method_exists($this, $action)) {
                $response = $this->$action($request);
            }

            return $this->json([
                'success' => true,
                'response' => $response ?? [],
                'errors' => $this->errors,
            ]);
        }
    }

    private function downloadCatalog()
    {
        $start = microtime(true);
        $curlCatalogDownload = new CurlCatalogDownload();
        $curlCatalogDownload->downloadCatalog();
        $elapsed = microtime(true) - $start;
        return $this->json([
            'time' => $this->getHumanTiming($elapsed),
        ]);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}