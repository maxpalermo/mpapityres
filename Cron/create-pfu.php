<?php
/**
 * Cron per aggiornamento PFU
 * Eseguibile con: php update_pfu.php
 */

define('_PS_MODE_DEV_', true);

// Dalla cartella Cron, risali alla root di PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/cron-template.php';

if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da CLI');
}

// Carica il modulo se necessario
require_once _PS_MODULE_DIR_ . 'mpapityres/mpapityres.php';

Context::getContext()->employee = new Employee(1);

try {
    echo "[" . date('Y-m-d H:i:s') . "] Inizio aggiornamento PFU\n";

    // Usa le classi del tuo modulo
    $createPfu = new MpSoft\MpApiTyres\Catalog\CreatePFU();

    // Esegui le operazioni necessarie
    // $result = $createPfu->someMethod();

    echo "[" . date('Y-m-d H:i:s') . "] Completato\n";
    exit(0);

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}