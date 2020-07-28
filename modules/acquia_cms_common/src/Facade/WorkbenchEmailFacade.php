<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\workbench_email\TemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Workbench Email.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class WorkbenchEmailFacade implements ContainerInjectionInterface {

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
   * The node type entity storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $nodeTypeStorage;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * WorkbenchEmailFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $workflow_storage
   *   The workflow entity storage handler.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $node_type_storage
   *   The node type entity storage handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(ConfigInstallerInterface $config_installer, ConfigEntityStorageInterface $workflow_storage, ConfigEntityStorageInterface $node_type_storage, LoggerChannelInterface $logger) {
    $this->configInstaller = $config_installer;
    $this->workflowStorage = $workflow_storage;
    $this->nodeTypeStorage = $node_type_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('config.installer'),
      $entity_type_manager->getStorage('workflow'),
      $entity_type_manager->getStorage('node_type'),
      $container->get('logger.factory')->get('acquia_cms')
    );
  }

  /**
   * Acts on a newly created Workbench Email template.
   *
   * Tries to enable the template in the workflow specified by the
   * acquia_cms.workflow_id third-party setting, and for every existing node
   * type that mentions the incoming template in its
   * acquia_cms.workbench_email_templates third-party setting.
   *
   * @param \Drupal\workbench_email\TemplateInterface $template
   *   The new workbench email template.
   */
  public function addTemplate(TemplateInterface $template) {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      $this->logger->debug('Skipping ' . __METHOD__ . ' during config sync.');
      return;
    }

    $variables = [
      '%template' => $template->label(),
    ];

    // Node types can opt into using this template by mentioning it their
    // acquia_cms.workbench_email_templates third-party setting.
    $node_types = $this->nodeTypeStorage->loadMultiple();
    $enabled_bundles = $template->getBundles();
    foreach ($node_types as $id => $node_type) {
      $variables['%node_type'] = $node_type->label();

      $email_templates = $node_type->getThirdPartySetting('acquia_cms', 'workbench_email_templates', []);
      if (array_key_exists($template->id(), $email_templates)) {
        $enabled_bundles["node:$id"] = "node:$id";
      }
      else {
        $this->logger->debug('The %template e-mail notification template was not enabled for the %node_type content type.', $variables);
      }
    }
    $template->setBundles($enabled_bundles)->save();

    // Add the template to the workflow specified in its third-party settings.
    // If the template does not specify a workflow, there's nothing to do.
    $workflow_id = $template->getThirdPartySetting('acquia_cms', 'workflow_id');
    if (empty($workflow_id)) {
      return;
    }

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->workflowStorage->load($workflow_id);
    if (empty($workflow)) {
      $variables['%workflow'] = $workflow_id;
      $this->logger->warning('Could not add the %template email notification template to the %workflow workflow because the workflow does not exist.', $variables);
      return;
    }

    $enabled_templates = $workflow->getThirdPartySetting('workbench_email', 'workbench_email_templates', []);
    $transitions = $template->getThirdPartySetting('acquia_cms', 'workflow_transitions', []);
    foreach ($transitions as $transition_id) {
      // Enable this template for the specified transition, but only if the
      // transition is not already associated with a template.
      $enabled_templates += [
        $transition_id => [
          $template->id() => $template->id(),
        ],
      ];
    }
    $workflow->setThirdPartySetting('workbench_email', 'workbench_email_templates', $enabled_templates);
    $this->workflowStorage->save($workflow);
  }

}
