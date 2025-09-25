<?php

namespace MpSoft\MpApiTyres\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_tyre") // Il prefisso verrà gestito nella migration
 */
class ProductTyreImageModel
{
  /** @var string */
  protected $pfx;

  /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
  protected $container;

  /** @var  \Doctrine\DBAL\Connection */
  protected $connection;

  /** @var \PrestaShop\PrestaShop\Adapter\LegacyContext */
  protected $context;

  /** @var  \Symfony\Component\Routing\RouterInterface */
  protected $router;

  /** @var \Doctrine\ORM\EntityManagerInterface */
  protected $entityManager;

  /** @var int */
  protected $id_lang;

  /** @var int */
  protected $id_shop;

  /** @var int */
  protected $id_currency;

  /** @var int */
  protected $id_warehouse;

  /** @var \PrestaShop\PrestaShop\Core\Localization\Locale */
  protected $locale;

  /** @var \Twig\Environment */
  protected $twig;

  /** @var array */
  protected $row;

  /**
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue
   */
  private $id;

  /** @ORM\Column(type="integer", length=255, nullable=false) */
  private $id_product;
  /** @ORM\Column(type="integer", length=255, nullable=true) */
  private $reference;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $image;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $image_tn;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $tyrelabel_link;
  /** @ORM\Column(type="string", length=10, nullable=true) */
  private $data_sheet;


  public function __construct($id = null)
  {
    /** @var string */
    $this->pfx = _DB_PREFIX_;
    /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
    $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
    $this->container = $container;
    /** @var  \Doctrine\DBAL\Connection */
    $this->connection = $container->get('doctrine.dbal.default_connection');
    /** @var \PrestaShop\PrestaShop\Adapter\LegacyContext */
    $this->context = $container->get('prestashop.adapter.legacy.context');
    /** @var \Symfony\Component\Routing\RouterInterface */
    $this->router = $container->get('router');
    /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
    $this->entityManager = $container->get('doctrine.orm.entity_manager');
    /** @var int */
    $this->id_lang = (int) $this->context->getContext()->language->id;
    /** @var int */
    $this->id_shop = (int) $this->context->getContext()->shop->id;
    /** @var int */
    $this->id_currency = (int) $this->context->getContext()->currency->id;
    /** @var int */
    $this->id_warehouse = (int) \Configuration::get("MPSTOCKADV_DEFAULT_WAREHOUSE");
    /** @var \PrestaShop\PrestaShop\Core\Localization\Locale */
    $this->locale = \Tools::getContextLocale($this->context->getContext());
    /** @var \Twig\Environment */
    $this->twig = $container->get('twig');

    if ($id) {
      $this->getTyre($id);
    }
  }


  public function getTyre($id)
  {
    $query = "
      SELECT * 
      FROM {$this->pfx}product_tyre_image 
      WHERE id = :id
    ";

    $row = $this->connection->fetchAssociative(
      $query,
      ['id' => $id]
    );

    $this->row = $row;
    return $row;
  }

  public function getRow()
  {
    return $this->row;
  }

  // --- GETTER & SETTER ---

  public function getId()
  {
    return $this->id;
  }
  public function getIdProduct()
  {
    return $this->id_product;
  }
  public function setIdProduct($v)
  {
    $this->id_product = $v;
    return $this;
  }
  public function getReference()
  {
    return $this->reference;
  }
  public function setReference($v)
  {
    $this->reference = $v;
    return $this;
  }
  public function getImage()
  {
    return $this->image;
  }
  public function setImage($v)
  {
    $this->image = $v;
    return $this;
  }
  public function getImageTn()
  {
    return $this->image_tn;
  }
  public function setImageTn($v)
  {
    $this->image_tn = $v;
    return $this;
  }
  public function getDataSheet()
  {
    return $this->data_sheet;
  }
  public function setDataSheet($v)
  {
    $this->data_sheet = $v;
    return $this;
  }
  public function getTyreLabelLink()
  {
    return $this->tyrelabel_link;
  }
  public function setTyreLabelLink($v)
  {
    $this->tyrelabel_link = $v;
    return $this;
  }

  /**
   * Restituisce la SQL per creare la tabella Doctrine con il prefisso dinamico di PrestaShop
   * @param string $prefix
   * @return string
   */
  public static function getCreateTableSql($prefix)
  {
    $table = $prefix . 'product_tyre_image';
    return <<<SQL
        CREATE TABLE IF NOT EXISTS `$table` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `id_product` INT DEFAULT NULL,
          `reference` VARCHAR(255) DEFAULT NULL,
          `image` VARCHAR(255) DEFAULT NULL,
          `image_tn` VARCHAR(255) DEFAULT NULL,
          `data_sheet` VARCHAR(255) DEFAULT NULL,
          `tyrelabel_link` VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB;
    SQL;
  }

  public function pagination($page, $limit, $orderBy = 'id', $orderWay = 'ASC')
  {
    $connection = $this->connection;
    $prefix = $connection->getDatabasePlatform()->getName() === 'mysql' ? _DB_PREFIX_ : '';
    $tableName = $prefix . 'product_tyre_image';

    $queryBuilder = $connection->createQueryBuilder();
    $queryBuilder->select('*')
      ->from($tableName)
      ->orderBy($orderBy, $orderWay)
      ->setFirstResult(($page - 1) * $limit)
      ->setMaxResults($limit);

    return $queryBuilder->execute()->fetchAllAssociative();
  }
}
