<?php

/**
 * Cron per download catalogo prodotti via API
 * Eseguibile con: php download-api.php
 */
require_once dirname(__FILE__) . '/cron-template.php';

if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da CLI');
}

// Carica il modulo se necessario
require_once _PS_MODULE_DIR_ . 'mpapityres/mpapityres.php';

Context::getContext()->employee = new Employee(1);

// Legge il flag opzionale --skip-download-csv dalla CLI
$downloadCsv = true;
$updateCsv = true;
$updateCatalog = true;

if (isset($argv) && is_array($argv)) {
    $downloadCsv = !(in_array('--no-download-csv', $argv, true));
    $updateCsv = !(in_array('--no-update-csv', $argv, true));
    $updateCatalog = !(in_array('--no-update-catalog', $argv, true));
}

try {
    $start = microtime(true);
    echo '[' . date('Y-m-d H:i:s') . "] INIZIO OPERAZIONI\n";

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";

    $module->cronImportFromCsv($downloadCsv, $updateCsv, $updateCatalog);

    $end = $module->humanReadableSeconds(microtime(true) - $start);
    echo "Operazione completata in {$end}\n";

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
