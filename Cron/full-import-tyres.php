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

/**
 * Cron per scaricamento file CSV
 * Eseguibile con: php download-csv.php
 */

// usa il bootstrap di prestashop
require_once dirname(__FILE__) . '/cron-template.php';

if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da CLI');
}

// Carica il modulo se necessario
require_once _PS_MODULE_DIR_ . 'mpapityres/mpapityres.php';

Context::getContext()->employee = new Employee(1);

try {
    $total_start = microtime(true);

    $start = microtime(true);

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');
    echo '[' . date('Y-m-d H:i:s') . "] MODULO CARICATO: {$module->name}\n";

    echo "\n\tABILITAZIONE MANUTENZIONE\n";
    $module->enableMaintenanceMode();

    echo "\n\t Scaricamento CSV in corso...";
    // 1 - Scarico il file CSV e lo estraggo
    $module->cronDownloadCsv();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    // ATTENDO UN SECONDO PER EVITARE DI ESSERE BLOCCATO DAL SERVER
    sleep(1);

    // 2 - Inserisco i record del file CSV nella tabella product_tyre
    $start = microtime(true);

    echo PHP_EOL . 'INIZIO IL PARSING DEL FILE CSV' . PHP_EOL;
    $module->cronParseCsv();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    // ATTENDO UN SECONDO PER EVITARE DI ESSERE BLOCCATO DAL SERVER
    sleep(1);

    // 3 - Scarico i dati da Tyre e li inserisco nella tabella product_tyre
    $start = microtime(true);

    echo PHP_EOL . 'INIZIO IL DOWNLOAD DEI PRODOTTI TRAMITE API' . PHP_EOL;
    $module->cronDownloadAPI();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    // ATTENDO UN SECONDO PER EVITARE DI ESSERE BLOCCATO DAL SERVER
    sleep(1);

    // 4 - Inserisco i record del file CSV nella tabella product_tyre
    $start = microtime(true);

    echo PHP_EOL . 'INIZIO IL PARSING DEI PRODOTTI TRAMITE API' . PHP_EOL;
    $module->cronParseAPI();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    // ATTENDO UN SECONDO PER EVITARE DI ESSERE BLOCCATO DAL SERVER
    sleep(1);

    echo PHP_EOL . 'DISABILITAZIONE PRODOTTI CON PREZZO 0' . PHP_EOL;
    $module->disablePriceZero();

    echo PHP_EOL . 'ATTIVAZIONE PRODOTTI TRAMITE API' . PHP_EOL;
    $module->activateProductsAPI();

    echo "\n\tDISABILITAZIONE MANUTENZIONE\n";
    $module->disableMaintenanceMode();

    $total_end = microtime(true);
    $total_time = $total_end - $total_start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Cron Completato in {$module->humanReadableSeconds($total_time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
