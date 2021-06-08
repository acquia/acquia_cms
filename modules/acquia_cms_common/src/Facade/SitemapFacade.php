<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SimplesitemapManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade to enable sitemap for the node types.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class SitemapFacade implements ContainerInjectionInterface {

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  private $configInstaller;

  /**
   * The sitemap generator service.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  private $generator;

  /**
   * The sitemap manager service.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  private $sitemapManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * WorkflowFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The sitemap generator service.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $sitemap_manager
   *   The sitemap manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(ConfigInstallerInterface $config_installer, Simplesitemap $generator, SimplesitemapManager $sitemap_manager, LoggerChannelInterface $logger) {
    $this->configInstaller = $config_installer;
    $this->generator = $generator;
    $this->sitemapManager = $sitemap_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.installer'),
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.manager'),
      $container->get('logger.factory')->get('acquia_cms')
    );
  }

  /**
   * Acts on a newly created node type.
   *
   * Tries to enable sitemap settings for the new node type by the
   * acquia_cms.sitemap_variant third-party setting.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The new node type.
   */
  public function enableSitemap(NodeTypeInterface $node_type) {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      $this->logger->debug('Skipping ' . __METHOD__ . ' during config sync.');
      return;
    }

    // If the node type does not specify the sitemap, there's nothing to do.
    $sitemap_variant = $node_type->getThirdPartySetting('acquia_cms_common', 'sitemap_variant');
    if (empty($sitemap_variant)) {
      return;
    }

    // Check if the entity type is enabled and variant exists for the sitemap.
    $all_default_variants = $this->sitemapManager->getSitemapVariants(SimplesitemapManager::DEFAULT_SITEMAP_TYPE);
    if ($this->generator->entityTypeIsEnabled('node') && array_key_exists($sitemap_variant, $all_default_variants)) {
      $this->generator->setBundleSettings('node', $node_type->id());
    }
    else {
      $this->logger->debug('The node entity type is not enabled or the variant doesn\'t exits in the sitemap');
    }
  }

}
