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

namespace MpSoft\MpApiTyres\Handlers;

class FetchJsonDistributorClassHandler extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
    private $fileName;
    private $list;
    private $action;
    private $params;

    public function __construct($action, $params)
    {
        parent::__construct();
        $this->fileName = _PS_MODULE_DIR_ . 'mpapityres/views/assets/json/distributors_list.json';
        $this->action = $action;
        $this->params = json_decode($params, true);
    }

    public function run()
    {
        switch ($this->action) {
            case 'get':
                return $this->get();
            case 'getPriceList':
                return $this->getPriceList($this->params['idT24']);
            case 'delete':
                return $this->delete();
            default:
                return false;
        }
    }

    /**
     * Write TyreList in a Json File
     * @param mixed $content
     * @return bool|int
     */
    public function write($list)
    {
        $fileContent = $this->read();

        if ($fileContent && $list) {
            $content = array_merge($fileContent, $list);
        } elseif ($list) {
            $content = $list;
        } else {
            return false;
        }

        $contentWrite = json_encode($content);

        return file_put_contents($this->fileName, $contentWrite);
    }

    /**
     * Read Json File
     * @return array|false
     */
    public function read($offset = 0, $limit = null)
    {
        if (file_exists($this->fileName)) {
            if ($offset == 0 && $limit == null) {
                return $this->jsonDecode(file_get_contents($this->fileName));
            } else {
                return $this->jsonDecode(file_get_contents($this->fileName), $offset, $limit);
            }
        }

        return [];
    }

    /**
     * Json Decode
     * @param mixed $content
     * @return array|false
     */
    public function jsonDecode($content, $offset = 0, $limit = null)
    {
        try {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return array_slice($content, $offset, $limit);
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
            return false;
        }
    }

    public function delete()
    {
        if (file_exists($this->fileName)) {
            return unlink($this->fileName);
        }

        return false;
    }


    public function get()
    {
        $this->list = $this->read();
        return $this->list;
    }

    public function getPriceList($idT24)
    {
        if (!$this->list) {
            $this->get();
        }

        foreach ($this->list as $item) {
            if ($item['idT24'] == $idT24) {
                return $item['priceList'];
            }
        }

        return [];
    }
}