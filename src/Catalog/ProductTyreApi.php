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

namespace MpSoft\MpApiTyres\Catalog;

use MpSoft\MpApiTyres\Handlers\SearchProductsCurlHandler;

class ProductTyreApi extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $minOrder = 16;
    private $resultChunk = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function run(array $list): int
    {
        $updated = 0;
        foreach ($list as $tyre) {
            $res = $this->updateProductTyre($tyre);
            if ($res['result'] > 0) {
                $updated++;
            }
            $this->resultChunk[] = $res;
        }

        return $updated;
    }

    /**
     * Summary of updateProductTyre
     * @param array $tyre
     * @return array{error: string, result: int}
     */
    private function updateProductTyre(array $tyre): array
    {
        $error = null;
        $distributors = $this->getDistributors($tyre);
        if (!$distributors) {
            return [
                'result' => 0,
                'error' => 'No price list',
            ];
        }
        $manufacturer = $this->getManufacturer($tyre);

        $connection = $this->connection;
        $connection->beginTransaction();
        $pfx = _DB_PREFIX_;
        $table = "{$pfx}product_tyre_api";
        $idT24 = (int) $tyre['idT24'];
        try {
            $result = $connection->executeStatement(
                "
                    REPLACE INTO {$table}
                    (
                        `id_t24`,
                        `id_solr`,
                        `ean`,
                        `description`,
                        `additional_description`,
                        `quantity`,
                        `type`,
                        `speed_index`,
                        `matchcode`,
                        `profile_name`,
                        `profile_description`,
                        `size`,
                        `image_url`,
                        `wholesaler_article_no`,
                        `is_dot`,
                        `is_demo`,
                        `is_discontinued`,
                        `supported_cooperations`,
                        `is_3pmsf`,
                        `id_data_source`,
                        `tyrelabel_energy_index`,
                        `tyrelabel_wetgrip_index`,
                        `tyrelabel_rolling_noise_value`,
                        `tyrelabel_rolling_noise_level`,
                        `tyrelabel_image_url`,
                        `tyrelabel_pricat_url`,
                        `tyrelabel_eprel_url`,
                        `tyrelabel_eu_directive_number`,
                        `tyrelabel_ice_grip`,
                        `tyrelabel_3pmsf`,
                        `tyrelabel_new`,
                        `date_add`,
                        `date_upd`
                    )
                    VALUES
                    (
                        :id_t24,
                        :id_solr,
                        :ean,
                        :description,
                        :additional_description,
                        :quantity,
                        :type,
                        :speed_index,
                        :matchcode,
                        :profile_name,
                        :profile_description,
                        :size,
                        :image_url,
                        :wholesaler_article_no,
                        :is_dot,
                        :is_demo,
                        :is_discontinued,
                        :supported_cooperations,
                        :is_3pmsf,
                        :id_data_source,
                        :tyrelabel_energy_index,
                        :tyrelabel_wetgrip_index,
                        :tyrelabel_rolling_noise_value,
                        :tyrelabel_rolling_noise_level,
                        :tyrelabel_image_url,
                        :tyrelabel_pricat_url,
                        :tyrelabel_eprel_url,
                        :tyrelabel_eu_directive_number,
                        :tyrelabel_ice_grip,
                        :tyrelabel_3pmsf,
                        :tyrelabel_new,
                        :date_add,
                        :date_upd
                    )
                ",
                [
                    'id_t24' => (int) $tyre['idT24'],
                    'id_solr' => $tyre['idSolr'],
                    'ean' => (string) $tyre['ean'],
                    'description' => (string) $tyre['description'],
                    'additional_description' => (string) $tyre['additionalDescription'],
                    'quantity' => (int) $tyre['quantity'],
                    'type' => (string) $tyre['type'],
                    'speed_index' => (string) $tyre['speedIndex'],
                    'matchcode' => (string) $tyre['matchcode'],
                    'profile_name' => (string) $tyre['profileName'],
                    'profile_description' => (string) $tyre['profileDescription'],
                    'size' => (string) $tyre['size'],
                    'image_url' => (string) $tyre['imageURL'],
                    'wholesaler_article_no' => (string) $tyre['wholesalerArticleNo'],
                    'is_dot' => (int) $tyre['isDot'],
                    'is_demo' => (int) $tyre['isDemo'],
                    'is_discontinued' => (int) $tyre['isDiscontinued'],
                    'supported_cooperations' => (string) $tyre['supportedCooperations'],
                    'is_3pmsf' => (int) $tyre['is3PMSF'],
                    'id_data_source' => (int) $tyre['idDataSource'],
                    'tyrelabel_energy_index' => (string) $tyre['tyreLabel']['energyIndex'],
                    'tyrelabel_wetgrip_index' => (string) $tyre['tyreLabel']['wetgripIndex'],
                    'tyrelabel_rolling_noise_value' => (string) $tyre['tyreLabel']['rollingNoiseValue'],
                    'tyrelabel_rolling_noise_level' => (string) $tyre['tyreLabel']['rollingNoiseLevel'],
                    'tyrelabel_image_url' => (string) $tyre['tyreLabel']['imageURL'],
                    'tyrelabel_pricat_url' => (string) $tyre['tyreLabel']['pricatUrl'],
                    'tyrelabel_eprel_url' => (string) $tyre['tyreLabel']['eprelUrl'],
                    'tyrelabel_eu_directive_number' => (string) $tyre['tyreLabel']['euDirectiveNumber'],
                    'tyrelabel_ice_grip' => (int) $tyre['tyreLabel']['iceGrip'],
                    'tyrelabel_3pmsf' => (int) $tyre['tyreLabel']['3PMSF'],
                    'tyrelabel_new' => (int) $tyre['tyreLabel']['newTyreLabel'],
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable $th) {
            $result = false;
            $error = $th->getMessage();
            $connection->rollBack();
        }

        if ($result) {
            $connection->commit();

            $this->updateDistributors($distributors, $idT24);
            $this->updateProductTyreManufacturer($manufacturer, $idT24);
        }

        return [
            'result' => (int) $result,
            'error' => $error,
        ];
    }

    /**
     * Summary of getDistributors
     * @param array $tyre L'elenco del listino prezzi o un array vuoto
     * @return array{id: int, value:float, country_code: string, currency: string, type:string, min_order: int}
     */
    private function getDistributors(array $tyre): array
    {
        $idTyre = $tyre['idT24'];
        $curlApiDownload = new SearchProductsCurlHandler();
        $distributors = $curlApiDownload->getDistributorsList($idTyre, ['it']);
        if (!$distributors) {
            return [];
        }

        return $distributors;
    }

    /**
     * Summary of getManufacturer
     * @param array $tyre
     * @return array{article_number: mixed, description: mixed, image_url: mixed, name: mixed}
     */
    private function getManufacturer(array $tyre): array
    {
        $manufacturer = [
            'name' => $tyre['manufacturerName'] ?? '',
            'description' => $tyre['manufacturerDescription'] ?? '',
            'image_url' => $tyre['manufacturerImage'] ?? '',
            'article_number' => $tyre['manufacturerArticleNumber'] ?? '',
        ];
        return $manufacturer;
    }

    private function updateDistributors(array $distributors, int $idT24): bool
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $connection->beginTransaction();
        $table = "{$pfx}product_tyre_distributor";

        try {
            $connection->executeStatement(
                "
                        DELETE FROM {$table}
                        WHERE id_t24 = :id_t24
                ",
                [
                    'id_t24' => (int) $idT24
                ]
            );
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog($th->getMessage(), 1, $th->getCode(), 'ProductTyreApi', $th->getLine());
            $connection->rollBack();
            return false;
        }

        foreach ($distributors as $list) {
            foreach ($list['priceList'] as $pricelist) {
                try {
                    $connection->executeStatement(
                        "
                            INSERT INTO {$table}
                            (
                                id_distributor,
                                id_t24,
                                id_pricelist,
                                stock,
                                value,
                                estimated_delivery,
                                date_add,
                                date_upd
                            )
                            VALUES
                            (
                                :id_distributor,
                                :id_t24,
                                :id_pricelist,
                                :stock,
                                :value,
                                :estimated_delivery,
                                :date_add,
                                :date_upd
                            )
                        ",
                        [
                            'id_distributor' => (int) $list['distributorId'],
                            'id_t24' => (int) $idT24,
                            'id_pricelist' => (int) $pricelist['id'],
                            'stock' => (int) $list['stock'],
                            'value' => (float) $pricelist['value'],
                            'estimated_delivery' => ($pricelist['estimatedDelivery'] ?? null),
                            'date_add' => date('Y-m-d H:i:s'),
                            'date_upd' => date('Y-m-d H:i:s'),
                        ]
                    );
                } catch (\Throwable $th) {
                    \PrestaShopLogger::addLog($th->getMessage(), 1, $th->getCode(), 'ProductTyreApi', $th->getLine());
                    $connection->rollBack();
                    return false;
                }
            }
        }

        $connection->commit();
        return true;
    }

    private function updateProductTyreManufacturer(array $manufacturer, int $idT24): bool
    {
        $connection = $this->connection;
        $pfx = _DB_PREFIX_;
        $connection->beginTransaction();
        $table = "{$pfx}product_tyre_manufacturer";

        try {
            $connection->executeStatement(
                "
                    REPLACE INTO {$table}
                    (
                        name,
                        description,
                        image_url,
                        date_add,
                        date_upd
                    )
                    VALUES
                    (
                        :name,
                        :description,
                        :image_url,
                        :date_add,
                        :date_upd
                    )
                ",
                [
                    'name' => (string) strtoupper($manufacturer['name']),
                    'description' => (string) $manufacturer['description'],
                    'image_url' => (string) strtolower($manufacturer['image_url']),
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]
            );

            $idManufacturer = $connection->lastInsertId();
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog($th->getMessage(), 1, $th->getCode(), 'ProductTyreApi', $th->getLine());
            $connection->rollBack();
            return false;
        }

        try {
            $table = "{$pfx}product_tyre_manufacturer_tyre";
            $connection->executeStatement(
                "REPLACE INTO {$table}
                (
                    id_manufacturer,
                    id_t24,
                    article_number
                )
                VALUES
                (
                    :id_manufacturer,
                    :id_t24,
                    :article_number
                )",
                [
                    'id_manufacturer' => (int) $idManufacturer,
                    'id_t24' => (int) $idT24,
                    'article_number' => (string) $manufacturer['article_number'],
                ]
            );
        } catch (\Throwable $th) {
            \PrestaShopLogger::addLog($th->getMessage(), 1, $th->getCode(), 'ProductTyreApi', $th->getLine());
            $connection->rollBack();
            return false;
        }

        $connection->commit();
        return true;
    }
}
