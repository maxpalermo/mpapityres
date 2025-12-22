<?php

/**
 * Cron per download catalogo prodotti via API
 * Eseguibile con: php download-api.php
 */
if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da CLI');
}

require_once dirname(__FILE__) . '/cron-template.php';
require_once _PS_MODULE_DIR_ . 'mpapityres/mpapityres.php';

Context::getContext()->employee = new Employee(1);

$funcName = null;

$options = getopt('', ['help', 'func-name:']);

if (isset($options['help'])) {
    exit(showHelp());
}

$funcName = $options['func-name'] ?? null;

if (!$funcName) {
    echo '
        call-func.php
        ============================
        Nessuna funzione specificata
        ============================
    ';

    exit(showHelp());
}

try {
    $start = microtime(true);

    // Usa le classi del tuo modulo
    /** @var MpApiTyres $module */
    $module = Module::getInstanceByName('mpapityres');

    echo "\tMODULO CARICATO: {$module->name}\n";
    if (method_exists($module, $funcName)) {
        $module->$funcName();
    } else {
        exit("Nessun metodo trovato con nome {$funcName}");
    }

    $end = microtime(true) - $start;
    $end = sprintf('%02d:%02d:%02d', ($end / 3600), ($end / 60 % 60), $end % 60);
    echo "Operazione completata in {$end}\n";

    exit(0);
} catch (Exception $e) {
    echo '[ERROR] ' . $e->getMessage() . "\n";
    exit(1);
}

function showHelp()
{
    echo '
        Usage: php call-func.php --func-name <func-name>
        
        Allowed functions:
          - deleteAllProducts
          - deleteWrongPfu
          - importFromCsv
          - cleanUp
        ' . PHP_EOL . PHP_EOL;

    exit(0);
}
