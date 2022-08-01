<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integration with Workflows and Content Moderation.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class WorkflowFacade implements ContainerInjectionInterface {

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  private $configInstaller;

  /**
   * The workflow entity storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $workflowStorage;

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
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $workflow_storage
   *   The workflow entity storage handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(ConfigInstallerInterface $config_installer, ConfigEntityStorageInterface $workflow_storage, LoggerChannelInterface $logger) {
    $this->configInstaller = $config_installer;
    $this->workflowStorage = $workflow_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.installer'),
      $container->get('entity_type.manager')->getStorage('workflow'),
      $container->get('logger.factory')->get('acquia_cms')
    );
  }

  /**
   * Acts on a newly created node type.
   *
   * Tries to add the new node type to a Content Moderation workflow specified
   * by the acquia_cms.workflow_id third-party setting. If the specified
   * workflow doesn't exist, or does exist but doesn't use Content Moderation, a
   * warning is logged.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The new node type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addNodeType(NodeTypeInterface $node_type) {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      $this->logger->debug('Skipping ' . __METHOD__ . ' during config sync.');
      return;
    }

    // If the node type does not specify a workflow, there's nothing to do.
    $workflow_id = $node_type->getThirdPartySetting('acquia_cms_common', 'workflow_id');
    if (empty($workflow_id)) {
      return;
    }

    $variables = [
      '%node_type' => $node_type->label(),
      '%workflow' => $workflow_id,
    ];

    // Ensure the workflow exists, and log a warning if it doesn't.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->workflowStorage->load($workflow_id);
    if (empty($workflow)) {
      $this->logger->warning('Could not add the %node_type content type to the %workflow workflow because the workflow does not exist.', $variables);
      return;
    }
    else {
      $variables['%workflow'] = $workflow->label();
    }

    $type_plugin = $workflow->getTypePlugin();
    if ($type_plugin instanceof ContentModerationInterface) {
      $type_plugin->addEntityTypeAndBundle('node', $node_type->id());
      $this->workflowStorage->save($workflow);
      $this->logger->info('Added the %node_type content type to the %workflow workflow.', $variables);
    }
    else {
      $this->logger->warning('Could not add the %node_type content type to the %workflow workflow because the workflow does not use Content Moderation.', $variables);
    }
  }

}
