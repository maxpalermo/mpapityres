<?php
/**
 * Cron per aggiornamento PFU
 * Eseguibile con: php update_pfu.php
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
    echo "[" . date('Y-m-d H:i:s') . "] Inizio scaricamento CSV\n";

    // Usa le classi del tuo modulo
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";


    $end = microtime(true);
    $time = $end - $start;
    echo "[" . date('Y-m-d H:i:s') . "] Completato in {$time} secondi\n";
    exit(0);

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}