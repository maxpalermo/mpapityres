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

namespace MpSoft\MpApiTyres\Models;

use Doctrine\ORM\Mapping as ORM;
use MpSoft\MpApiTyres\Helpers\ExecuteSqlHelper;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_tyre") // Il prefisso verrà gestito nella migration
 */
class ProductTyreDistributorModel extends \MpSoft\MpApiTyres\Helpers\DependencyHelper
{
  /** @var array */
  protected $row;

  /** @var string */
  protected $tableName;

  /** @var string */
  protected $idField;

  /**
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue
   */
  public $id_distributor;

  /** @ORM\Column(type="integer", length=255, nullable=false) */
  public $id_t24;

  /** @ORM\Column(type="integer", length=255, nullable=false) */
  public $id_pricelist;

  /** @ORM\Column(type="integer", length=255, nullable=false) */
  public $stock;

  /** @ORM\Column(type="integer", length=255, nullable=false) */
  public $min_order;

  /** @ORM\Column(type="decimal", length=255, nullable=false) */
  public $value;

  /** @ORM\Column(type="string", length=10, nullable=true) */
  public $estimated_delivery;

  /** @ORM\Column(type="datetime", nullable=false) */
  public $date_add;

  /** @ORM\Column(type="datetime", nullable=true) */
  public $date_upd;


  public function __construct($id = null)
  {
    parent::__construct();
    $this->tableName = _DB_PREFIX_ . 'product_tyre_distributor';
    $this->idField = 'id_distributor';

    if ($id) {
      $this->get($id);
    }
  }
  public function get($id)
  {
    $query = "
      SELECT * 
      FROM {$this->tableName} 
      WHERE {$this->idField} = :id
    ";

    $row = $this->connection->fetchAssociative(
      $query,
      ['id' => $id]
    );

    $this->row = $row;
    return $row;
  }

  /**
   * Summary of save: If id is passed, update, otherwise insert
   * @param mixed $id
   * @return bool, true if success, false otherwise and fill $this->errors
   */
  public function save($id = null)
  {
    if ($id) {
      $query = "
        UPDATE {$this->tableName} 
        SET 
          id_t24 = :id_t24,
          id_pricelist = :id_pricelist,
          stock = :stock,
          min_order = :min_order,
          value = :value,
          estimated_delivery = :estimated_delivery,
          date_add = :date_add,
          date_upd = :date_upd
        WHERE {$this->idField} = :id
      ";
    } else {
      $query = "
        INSERT INTO {$this->tableName} 
        (
          id_t24, 
          id_pricelist, 
          stock, 
          min_order, 
          value, 
          estimated_delivery, 
          date_add, 
          date_upd
        ) 
        VALUES (
          :id_t24, 
          :id_pricelist, 
          :stock, 
          :min_order, 
          :value, 
          :estimated_delivery, 
          :date_add, 
          :date_upd
        )
      ";
    }

    $data = [
      'id_t24' => $this->id_t24,
      'id_pricelist' => $this->id_pricelist,
      'stock' => $this->stock,
      'min_order' => $this->min_order,
      'value' => $this->value,
      'estimated_delivery' => $this->estimated_delivery,
      'date_add' => $this->date_add,
      'date_upd' => $this->date_upd,
    ];

    if ($id) {
      $data['id'] = $id;
    }

    try {
      $this->connection->executeStatement(
        $query,
        $data
      );
    } catch (\Throwable $th) {
      $this->errors[] = $th->getMessage();
      return false;
    }

    return true;
  }

  public function delete($id)
  {
    $query = "
      DELETE FROM {$this->tableName} 
      WHERE {$this->idField} = :id
    ";

    return $this->connection->executeStatement(
      $query,
      ['id' => $id]
    );
  }

  public function getErrors()
  {
    return $this->errors;
  }

  public static function install()
  {
    $executeSqlHelper = new ExecuteSqlHelper();
    return $executeSqlHelper->run('product_tyre_distributor');
  }

  public function getDistributorsByProductId($idT24)
  {
    $query = "
      SELECT * 
      FROM {$this->tableName} 
      WHERE id_t24 = :id_t24
    ";

    return $this->connection->fetchAllAssociative(
      $query,
      ['id_t24' => $idT24]
    );
  }

  public function deleteByProductId($idT24)
  {
    $query = "
      DELETE FROM {$this->tableName} 
      WHERE id_t24 = :id_t24
    ";

    return $this->connection->executeStatement(
      $query,
      ['id_t24' => $idT24]
    );
  }

  public function truncate()
  {
    $query = "
      TRUNCATE TABLE {$this->tableName}
    ";

    return $this->connection->executeStatement($query);
  }

}
