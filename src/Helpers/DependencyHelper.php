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

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
class DependencyHelper
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

    protected $errors = [];

    public function __construct()
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
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        if ($this->container->has('templating') && $this->container->get('templating')->supports($view)) {
            @trigger_error('Using the "templating" service is deprecated since version 4.3 and will be removed in 5.0; use Twig instead.', \E_USER_DEPRECATED);

            return $this->container->get('templating')->render($view, $parameters);
        }

        if (!$this->container->has('twig')) {
            throw new \LogicException('You can not use the "renderView" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
        }

        return $this->container->get('twig')->render($view, $parameters);
    }

    public function deleteChunks()
    {
        $outputDir = _PS_UPLOAD_DIR_ . 'CSV/chunks';
        $files = glob($outputDir . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}