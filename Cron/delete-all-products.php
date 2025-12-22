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
try {
    $start = microtime(true);

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";

    $module->deleteAllProducts();

    $end = microtime(true) - $start;
    $end = sprintf('%02d:%02d:%02d', ($end / 3600), ($end / 60 % 60), $end % 60);
    echo "Operazione completata in {$end}\n";

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}
