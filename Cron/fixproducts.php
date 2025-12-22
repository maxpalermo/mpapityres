<?php

use MpSoft\MpApiTyres\Models\ModelProductCsvTyre;

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
$cleanup = false;
$recalcweight = false;
if (isset($argv) && is_array($argv)) {
    $cleanup = in_array('--cleanup', $argv, true);
    $recalcweight = in_array('--recalcweight', $argv, true);
}

if (!$cleanup && !$recalcweight) {
    echo "==================================================\n";
    echo "Opzione non valida. Usa --cleanup o --recalcweight\n";
    echo "==================================================\n";
    exit(1);
}

try {
    $start = microtime(true);
    echo '[' . date('Y-m-d H:i:s') . "] INIZIO OPERAZIONI\n";

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";

    if ($cleanup) {
        echo "\tOPZIONE --cleanup ATTIVA: Pulizia Prodotti\n";
        $module->cleanUp();
    }

    if ($recalcweight) {
        echo "\tOPZIONE --recalcweight ATTIVA: Ricalcolo Peso\n";
        $module->recalcWeight();
    }

    $end = ModelProductCsvTyre::humanReadableSeconds(microtime(true) - $start);
    echo "Operazione completata in {$end}\n";

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
