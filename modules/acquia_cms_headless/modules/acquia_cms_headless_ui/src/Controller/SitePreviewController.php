<?php

namespace Drupal\acquia_cms_headless_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\next\NextEntityTypeManager;
use Drupal\next\Plugin\SitePreviewerManagerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a controller for the site preview of a node.
 *
 * @internal
 *   This is an internal part of Acquia CMS Headless and may be changed or
 *   removed at any time without warning. External code should not extend or
 *   use this class in any way!
 */
class SitePreviewController extends ControllerBase {

  /**
   * The next entity type manager.
   *
   * @var \Drupal\next\NextEntityTypeManager
   */
  protected $nextEntityTypeManager;

  /**
   * The site previewer manager.
   *
   * @var \Drupal\next\Plugin\SitePreviewerManagerInterface
   */
  protected $sitePreviewerManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SitePreviewController constructor.
   *
   * @param \Drupal\next\NextEntityTypeManager $next_entity_type_manager
   *   Next entity type manager.
   * @param \Drupal\next\Plugin\SitePreviewerManagerInterface $site_preview_manager
   *   Site preview manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(NextEntityTypeManager $next_entity_type_manager,
  SitePreviewerManagerInterface $site_preview_manager,
  EntityTypeManagerInterface $entity_type_manager) {
    $this->nextEntityTypeManager = $next_entity_type_manager;
    $this->sitePreviewerManager = $site_preview_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('next.entity_type.manager'),
      $container->get('plugin.manager.next.site_previewer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the node title for preview.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node Entity Object.
   *
   * @return string
   *   Preview node title.
   */
  public function nodePreviewTitle(Node $node): string {
    return 'Preview: ' . $node->getTitle();
  }

  /**
   * Displays the next.js site preview of a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node Entity Object.
   *
   * @return array
   *   Site preview data.
   */
  public function nodePreview(Node $node): array {
    $storage = $this->entityTypeManager->getStorage($node->getEntityTypeId());
    $revision = $storage->loadRevision($storage->getLatestRevisionId($node->id()));
    $nextEntityTypeConfig = $this->nextEntityTypeManager->getConfigForEntityType($revision->getEntityTypeId(), $revision->bundle());
    if (!$nextEntityTypeConfig) {
      $response = new RedirectResponse('/admin/config/services/next/entity-types');
      $response->send();
    }
    $sites = $nextEntityTypeConfig->getSiteResolver()->getSitesForEntity($revision);
    if (!count($sites)) {
      throw new \Exception('Next.js sites for the entity could not be resolved.');
    }

    $config = $this->config('next.settings');
    $sitePreviewerId = $config->get('site_previewer') ?? 'iframe';

    /** @var \Drupal\next\Plugin\SitePreviewerInterface $site_previewer */
    $sitePreviewer = $this->sitePreviewerManager->createInstance($sitePreviewerId, $config->get('site_previewer_configuration') ?? []);
    if (!$sitePreviewer) {
      throw new PluginNotFoundException('Invalid site previewer.');
    }

    // Build preview.
    $preview = $sitePreviewer->render($revision, $sites);

    $context = [
      'plugin' => $sitePreviewer,
      'entity' => $revision,
      'sites' => $sites,
    ];

    // Allow modules to alter the preview.
    $this->moduleHandler()->alter('next_site_preview', $preview, $context);

    return $preview;
  }

}
