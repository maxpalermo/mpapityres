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

namespace MpSoft\MpApiTyres\Configuration;

final class ConfigValues
{
    public $MPAPITYRES_CSV_ENDPOINT;
    public $MPAPITYRES_CSV_ACCOUNT_ID;
    public $MPAPITYRES_CSV_TOKEN;
    public $MPAPITYRES_USERNAME;
    public $MPAPITYRES_PASSWORD;
    public $MPAPITYRES_NO_LABEL_HASH;
    public $MPAPITYRES_NO_IMAGE_HASH;
    public $MPAPITYRES_DEFAULT_CATEGORY;
    public $MPAPITYRES_ID_TAX_RULES_GROUP;
    public $MPAPITYRES_CURL_TIMEOUT;
    public $MPAPITYRES_API_ENDPOINT;
    public $MPAPITYRES_API_TOKEN;
    public $MPAPITYRES_API_MIN_STOCK;
    public $MPAPITYRES_RICARICO_C1;
    public $MPAPITYRES_RICARICO_C2;
    public $MPAPITYRES_RICARICO_C3;
    public $MPAPITYRES_RICARICO_DEFAULT;
    public $MPAPITYRES_CRON_TIME_BETWEEN_UPDATES;
    public $MPAPITYRES_CRON_DOWNLOAD_UPDATED_DATE;
    public $MPAPITYRES_CRON_DOWNLOAD_OFFSET;
    public $MPAPITYRES_CRON_DOWNLOAD_STATUS;
    public $MPAPITYRES_CRON_DOWNLOAD_UPDATED;
    public $MPAPITYRES_CRON_RELOAD_IMAGES_UPDATED_DATE;
    public $MPAPITYRES_CRON_IMPORT_OFFSET;
    public $MPAPITYRES_CRON_IMPORT_UPDATED;
    public $MPAPITYRES_CRON_IMPORT_STATUS;
    public $MPAPITYRES_CRON_IMPORT_UPDATED_DATE;


    private static $instance = null;
    private $logs = [];

    // ✅ Costruttore privato per prevenire istanziazione
    private function __construct()
    {
        $this->readConfigValues();
    }

    // ✅ Previene la clonazione
    private function __clone()
    {
    }

    // ✅ Previene la deserializzazione
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    public static function getInstance(): ConfigValues
    {
        if (self::$instance === null) {
            self::$instance = new ConfigValues();
        }
        return self::$instance;
    }

    public function log(string $message): void
    {
        $this->logs[] = date('Y-m-d H:i:s') . ' - ' . $message;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function readConfigValues()
    {
        $props = $this->getModuleProperties();
        foreach ($props as $prop) {
            $this->$prop = \Configuration::get($prop);
        }
    }

    public function writeConfigValues()
    {
        $props = $this->getModuleProperties();
        foreach ($props as $prop) {
            \Configuration::updateValue($prop, $this->$prop);
        }
    }

    public function getConfigValues()
    {
        $props = $this->getModuleProperties();
        $result = [];
        foreach ($props as $prop) {
            $result[$prop] = $this->$prop;
        }

        return $result;
    }

    protected function getModuleProperties()
    {
        $properties = get_class_vars(get_class($this));
        $result = [];
        foreach ($properties as $key => $value) {
            if (preg_match('/^MPAPITYRES_/', $key)) {
                $result[] = $key;
            }
        }

        return $result;
    }

    public function setValue($key, $value)
    {
        if (!isset($this->$key)) {
            throw new \Exception("Property $key does not exist");
        }

        $this->$key = $value;
        return \Configuration::updateValue($key, $this->$key);
    }

    public function getCategoryIdByName($name = null)
    {
        if (!$name) {
            $name = $this->MPAPITYRES_DEFAULT_CATEGORY;
        }

        $id_lang = (int) \Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $name = str_replace("'", "''", $name);
        $name = pSQL($name);

        $query->select("id_category")
            ->from('category_lang')
            ->where("name = '{$name}'")
            ->where("id_lang = {$id_lang}");

        return (int) $db->getValue($query);
    }

    public function getTaxRulesGroups()
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $list = \TaxRulesGroup::getTaxRulesGroups($id_lang);

        return $list;
    }

    public function getCsvEndpointUrl()
    {
        $endpoint = $this->MPAPITYRES_CSV_ENDPOINT;
        $token = $this->MPAPITYRES_CSV_TOKEN;
        $account_id = $this->MPAPITYRES_CSV_ACCOUNT_ID;

        $url = str_replace(
            [
                '{token}',
                '{accountId}'
            ],
            [
                $token,
                $account_id
            ],
            $endpoint
        );

        return $url;
    }

}