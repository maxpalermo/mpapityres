<?php

/**
 * Script CLI per PrestaShop 8
 * Eseguibile con: php nome_script.php
 */

// Definisci che siamo in modalità CLI
define('_PS_MODE_DEV_', false);

ini_set('memory_limit', '2G');

// Percorso alla root di PrestaShop (adatta in base alla posizione dello script)
require_once dirname(__FILE__) . '/../../../config/config.inc.php';

// Verifica che lo script sia eseguito da CLI
if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da linea di comando');
}

// Inizializza il contesto
Context::getContext()->employee = new Employee(1);  // Usa un employee valido
