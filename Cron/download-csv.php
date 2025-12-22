<?php

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
    ob_start();

    $start = microtime(true);
    echo '[' . date('Y-m-d H:i:s') . "] Inizio scaricamento CSV\n";

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";

    $module->cronDownloadCsv();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;
    ob_end_flush();

    ob_start();
    $start = microtime(true);
    echo PHP_EOL . 'PROCEDO AL PARSING DEL FILE CSV' . PHP_EOL;
    $module->cronParseCsv();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;
    ob_end_flush();

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
