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

namespace MpSoft\MpApiTyres\Helpers;

class ExecuteSqlHelper extends DependencyHelper
{
    private $errors = [];
    public function run($sqlname)
    {
        $sql = file_get_contents(_PS_MODULE_DIR_ . 'mpapityres' . '/sql/' . $sqlname . '.sql');
        if (!$sql) {
            $this->errors[] = "File {$sqlname}.sql non trovato";
            return false;
        }

        $prefix = _DB_PREFIX_;
        //Separo le query
        $queries = explode(";", $sql);

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;

        try {
            foreach ($queries as $query) {
                if (trim($query) === '') {
                    continue;
                }
                $query = str_replace("{pfx}", $prefix, $query);
                $connection->executeStatement(trim($query));
            }
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
            $connection->rollBack();
            return false;
        }

        return true;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}