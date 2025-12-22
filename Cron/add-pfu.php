<?php

/**
 * Cron per download catalogo prodotti via API
 * Eseguibile con: php download-api.php
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
    $start = microtime(true);
    echo '[' . date('Y-m-d H:i:s') . "] Inizio associazione prodotti con PFU\n";

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";

    echo PHP_EOL . "PROCEDO ALL'ASSOCIAZIONE" . PHP_EOL;
    $module->addPfu();

    $end = microtime(true);
    $time = $end - $start;
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . "] Completato in {$module->humanReadableSeconds($time)} secondi\n";
    echo PHP_EOL . '-----------------------------' . PHP_EOL;

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
