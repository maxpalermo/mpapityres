<?php

namespace MpSoft\MpApiTyres\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_tyre") // Il prefisso verrà gestito nella migration
 */
class ProductTyreModel
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
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $matchcode;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $outer_diameter;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $height;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $width;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $tyre_type;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $inner_diameter;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $brandname;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $profile;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $articlename;
  /** @ORM\Column(type="text", nullable=true) */
  private $description;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $manufacturer_number;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $usage;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $type;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $load_index;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $speed_index;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $runflat;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $ms;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $three_pmfs;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $da_decke;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $price_1;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $price_4;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $avg_price;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $price_anonym;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $price_rvo;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $availability;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $expected_delivery_date;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $direct_link;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $info;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $image;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $image_tn;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $tyrelabel_link;
  /** @ORM\Column(type="string", length=10, nullable=true) */
  private $energy_efficiency_index;
  /** @ORM\Column(type="string", length=10, nullable=true) */
  private $wet_grip_index;
  /** @ORM\Column(type="string", length=10, nullable=true) */
  private $noise_index;
  /** @ORM\Column(type="string", length=10, nullable=true) */
  private $noise_decible;
  /** @ORM\Column(type="string", length=50, nullable=true) */
  private $vehicle_class;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $ice_grip;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $data_sheet;
  /** @ORM\Column(type="boolean", nullable=true) */
  private $demo;
  /** @ORM\Column(type="string", length=255, nullable=true) */
  private $dot;
  /** @ORM\Column(type="string", length=4, nullable=true) */
  private $dot_year;
  /** @ORM\Column(type="decimal", precision=10, scale=2, nullable=true) */
  private $price_anonym_one;
  /** @ORM\Column(type="text", nullable=true) */
  private $manufacturer_description;


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
      FROM {$this->pfx}product_tyre 
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
  public function getMatchcode()
  {
    return $this->matchcode;
  }
  public function setMatchcode($v)
  {
    $this->matchcode = $v;
    return $this;
  }
  public function getOuterDiameter()
  {
    return $this->outer_diameter;
  }
  public function setOuterDiameter($v)
  {
    $this->outer_diameter = $v;
    return $this;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setHeight($v)
  {
    $this->height = $v;
    return $this;
  }
  public function getWidth()
  {
    return $this->width;
  }
  public function setWidth($v)
  {
    $this->width = $v;
    return $this;
  }
  public function getTyreType()
  {
    return $this->tyre_type;
  }
  public function setTyreType($v)
  {
    $this->tyre_type = $v;
    return $this;
  }
  public function getInnerDiameter()
  {
    return $this->inner_diameter;
  }
  public function setInnerDiameter($v)
  {
    $this->inner_diameter = $v;
    return $this;
  }
  public function getBrandname()
  {
    return $this->brandname;
  }
  public function setBrandname($v)
  {
    $this->brandname = $v;
    return $this;
  }
  public function getProfile()
  {
    return $this->profile;
  }
  public function setProfile($v)
  {
    $this->profile = $v;
    return $this;
  }
  public function getArticlename()
  {
    return $this->articlename;
  }
  public function setArticlename($v)
  {
    $this->articlename = $v;
    return $this;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDescription($v)
  {
    $this->description = $v;
    return $this;
  }
  public function getManufacturerNumber()
  {
    return $this->manufacturer_number;
  }
  public function setManufacturerNumber($v)
  {
    $this->manufacturer_number = $v;
    return $this;
  }
  public function getUsage()
  {
    return $this->usage;
  }
  public function setUsage($v)
  {
    $this->usage = $v;
    return $this;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setType($v)
  {
    $this->type = $v;
    return $this;
  }
  public function getLoadIndex()
  {
    return $this->load_index;
  }
  public function setLoadIndex($v)
  {
    $this->load_index = $v;
    return $this;
  }
  public function getSpeedIndex()
  {
    return $this->speed_index;
  }
  public function setSpeedIndex($v)
  {
    $this->speed_index = $v;
    return $this;
  }
  public function getRunflat()
  {
    return $this->runflat;
  }
  public function setRunflat($v)
  {
    $this->runflat = $v;
    return $this;
  }
  public function getMs()
  {
    return $this->ms;
  }
  public function setMs($v)
  {
    $this->ms = $v;
    return $this;
  }
  public function getThreePmfs()
  {
    return $this->three_pmfs;
  }
  public function setThreePmfs($v)
  {
    $this->three_pmfs = $v;
    return $this;
  }
  public function getDaDecke()
  {
    return $this->da_decke;
  }
  public function setDaDecke($v)
  {
    $this->da_decke = $v;
    return $this;
  }
  public function getPrice1()
  {
    return $this->price_1;
  }
  public function setPrice1($v)
  {
    $this->price_1 = $v;
    return $this;
  }
  public function getPrice4()
  {
    return $this->price_4;
  }
  public function setPrice4($v)
  {
    $this->price_4 = $v;
    return $this;
  }
  public function getAvgPrice()
  {
    return $this->avg_price;
  }
  public function setAvgPrice($v)
  {
    $this->avg_price = $v;
    return $this;
  }
  public function getPriceAnonym()
  {
    return $this->price_anonym;
  }
  public function setPriceAnonym($v)
  {
    $this->price_anonym = $v;
    return $this;
  }
  public function getPriceRvo()
  {
    return $this->price_rvo;
  }
  public function setPriceRvo($v)
  {
    $this->price_rvo = $v;
    return $this;
  }
  public function getAvailability()
  {
    return $this->availability;
  }
  public function setAvailability($v)
  {
    $this->availability = $v;
    return $this;
  }
  public function getExpectedDeliveryDate()
  {
    return $this->expected_delivery_date;
  }
  public function setExpectedDeliveryDate($v)
  {
    $this->expected_delivery_date = $v;
    return $this;
  }
  public function getDirectLink()
  {
    return $this->direct_link;
  }
  public function setDirectLink($v)
  {
    $this->direct_link = $v;
    return $this;
  }
  public function getInfo()
  {
    return $this->info;
  }
  public function setInfo($v)
  {
    $this->info = $v;
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
  public function getTyrelabelLink()
  {
    return $this->tyrelabel_link;
  }
  public function setTyrelabelLink($v)
  {
    $this->tyrelabel_link = $v;
    return $this;
  }
  public function getEnergyEfficiencyIndex()
  {
    return $this->energy_efficiency_index;
  }
  public function setEnergyEfficiencyIndex($v)
  {
    $this->energy_efficiency_index = $v;
    return $this;
  }
  public function getWetGripIndex()
  {
    return $this->wet_grip_index;
  }
  public function setWetGripIndex($v)
  {
    $this->wet_grip_index = $v;
    return $this;
  }
  public function getNoiseIndex()
  {
    return $this->noise_index;
  }
  public function setNoiseIndex($v)
  {
    $this->noise_index = $v;
    return $this;
  }
  public function getNoiseDecible()
  {
    return $this->noise_decible;
  }
  public function setNoiseDecible($v)
  {
    $this->noise_decible = $v;
    return $this;
  }
  public function getVehicleClass()
  {
    return $this->vehicle_class;
  }
  public function setVehicleClass($v)
  {
    $this->vehicle_class = $v;
    return $this;
  }
  public function getIceGrip()
  {
    return $this->ice_grip;
  }
  public function setIceGrip($v)
  {
    $this->ice_grip = $v;
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
  public function getDemo()
  {
    return $this->demo;
  }
  public function setDemo($v)
  {
    $this->demo = $v;
    return $this;
  }
  public function getDot()
  {
    return $this->dot;
  }
  public function setDot($v)
  {
    $this->dot = $v;
    return $this;
  }
  public function getDotYear()
  {
    return $this->dot_year;
  }
  public function setDotYear($v)
  {
    $this->dot_year = $v;
    return $this;
  }
  public function getPriceAnonymOne()
  {
    return $this->price_anonym_one;
  }
  public function setPriceAnonymOne($v)
  {
    $this->price_anonym_one = $v;
    return $this;
  }
  public function getManufacturerDescription()
  {
    return $this->manufacturer_description;
  }
  public function setManufacturerDescription($v)
  {
    $this->manufacturer_description = $v;
    return $this;
  }

  /**
   * Restituisce la SQL per creare la tabella Doctrine con il prefisso dinamico di PrestaShop
   * @param string $prefix
   * @return string
   */
  public static function getCreateTableSql($prefix)
  {
    $table = $prefix . 'product_tyre';
    return <<<SQL
        CREATE TABLE IF NOT EXISTS `$table` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `matchcode` VARCHAR(255) DEFAULT NULL,
          `outer_diameter` VARCHAR(255) DEFAULT NULL,
          `height` VARCHAR(255) DEFAULT NULL,
          `width` VARCHAR(255) DEFAULT NULL,
          `tyre_type` VARCHAR(255) DEFAULT NULL,
          `inner_diameter` VARCHAR(255) DEFAULT NULL,
          `brandname` VARCHAR(255) DEFAULT NULL,
          `profile` VARCHAR(255) DEFAULT NULL,
          `articlename` VARCHAR(255) DEFAULT NULL,
          `description` TEXT DEFAULT NULL,
          `manufacturer_number` VARCHAR(255) DEFAULT NULL,
          `usage` VARCHAR(255) DEFAULT NULL,
          `type` VARCHAR(255) DEFAULT NULL,
          `load_index` VARCHAR(255) DEFAULT NULL,
          `speed_index` VARCHAR(255) DEFAULT NULL,
          `runflat` TINYINT(1) DEFAULT NULL,
          `ms` TINYINT(1) DEFAULT NULL,
          `three_pmfs` TINYINT(1) DEFAULT NULL,
          `da_decke` TINYINT(1) DEFAULT NULL,
          `price_1` DECIMAL(10,2) DEFAULT NULL,
          `price_4` DECIMAL(10,2) DEFAULT NULL,
          `avg_price` DECIMAL(10,2) DEFAULT NULL,
          `price_anonym` DECIMAL(10,2) DEFAULT NULL,
          `price_rvo` DECIMAL(10,2) DEFAULT NULL,
          `availability` VARCHAR(255) DEFAULT NULL,
          `expected_delivery_date` VARCHAR(255) DEFAULT NULL,
          `direct_link` VARCHAR(255) DEFAULT NULL,
          `info` VARCHAR(255) DEFAULT NULL,
          `image` VARCHAR(255) DEFAULT NULL,
          `image_tn` VARCHAR(255) DEFAULT NULL,
          `tyrelabel_link` VARCHAR(255) DEFAULT NULL,
          `energy_efficiency_index` VARCHAR(10) DEFAULT NULL,
          `wet_grip_index` VARCHAR(10) DEFAULT NULL,
          `noise_index` VARCHAR(10) DEFAULT NULL,
          `noise_decible` VARCHAR(10) DEFAULT NULL,
          `vehicle_class` VARCHAR(50) DEFAULT NULL,
          `ice_grip` TINYINT(1) DEFAULT NULL,
          `data_sheet` VARCHAR(255) DEFAULT NULL,
          `demo` TINYINT(1) DEFAULT NULL,
          `dot` VARCHAR(255) DEFAULT NULL,
          `dot_year` VARCHAR(4) DEFAULT NULL,
          `price_anonym_one` DECIMAL(10,2) DEFAULT NULL,
          `manufacturer_description` TEXT DEFAULT NULL
        ) ENGINE=InnoDB;
    SQL;
  }

  public function pagination($page, $limit, $orderBy = 'id', $orderWay = 'ASC')
  {
    $connection = $this->connection;
    $prefix = $connection->getDatabasePlatform()->getName() === 'mysql' ? _DB_PREFIX_ : '';
    $tableName = $prefix . 'product_tyre';

    $queryBuilder = $connection->createQueryBuilder();
    $queryBuilder->select('*')
      ->from($tableName)
      ->orderBy($orderBy, $orderWay)
      ->setFirstResult(($page - 1) * $limit)
      ->setMaxResults($limit);

    return $queryBuilder->execute()->fetchAllAssociative();
  }
}
