<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\workbench_email\TemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integration with Workflows, Node and Template.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class TemplateFacade implements ContainerInjectionInterface {

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
   * TemplateFacade constructor.
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
    return new static(
      $container->get('config.installer'),
      $container->get('entity_type.manager')->getStorage('workflow'),
      $container->get('entity_type.manager')->getStorage('node_type'),
      $container->get('logger.factory')->get('acquia_cms')
    );
  }

  /**
   * Acts on a newly created workbench email template type.
   *
   * Tries to add the email template to a Content Moderation workflow specified
   * by the acquia_cms.workflow_id third-party setting. Also, tries to enable
   * the bundles in the email template based on the node type third-party
   * setting acquia_cms.workbench_email_templates.
   *
   * @param \Drupal\workbench_email\TemplateInterface $template
   *   The new workbench email template type.
   */
  public function addTemplateType(TemplateInterface $template) {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      $this->logger->debug('Skipping ' . __METHOD__ . ' during config sync.');
      return;
    }

    $variables = [
      '%template_type' => $template->label(),
    ];

    // Enabling bundles in the email template based on the node third-party
    // settings.
    $node_types = $this->nodeTypeStorage->loadMultiple();
    foreach ($node_types as $node_type) {
      $variables['%node_type'] = $node_type->label();
      // Adding bundles in the email template based on the presence of email
      // template third-party setting in the node type.
      $email_templates = $node_type->getThirdPartySetting('acquia_cms', 'workbench_email_templates', []);
      if (array_key_exists($template->id(), $email_templates)) {
        $bundles = $template->getBundles();
        // Appending the node type in the existing bundle list to enable the
        // workbench notification.
        $bundles['node:' . $node_type->id()] = 'node:' . $node_type->id();
        $template->setBundles($bundles)->save();
      }
      else {
        $this->logger->debug('%node_type node type is not enabled for %template_type email notification', $variables);
      }
    }

    // If the workbench email templte type does not specify a workflow, there's
    // nothing to do.
    $workflow_id = $template->getThirdPartySetting('acquia_cms', 'workflow_id', []);
    $transitions = $template->getThirdPartySetting('acquia_cms', 'workflow_transitions', []);
    if (empty($workflow_id) || empty($transitions)) {
      return;
    }
    // Ensure the workflow exists, and log a warning if it doesn't.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->workflowStorage->load($workflow_id);
    $variables['%workflow'] = $workflow_id;
    if (empty($workflow)) {
      $this->logger->warning('Could not add the workbench email templates to the %workflow workflow because the workflow does not exist.', $variables);
      return;
    }
    $values = $workflow->getThirdPartySetting('workbench_email', 'workbench_email_templates', []);
    foreach ($transitions as $transition_id) {
      $values[$transition_id][$template->id()] = $template->id();
    }
    $workflow->setThirdPartySetting('workbench_email', 'workbench_email_templates', $values);
    $workflow->save();
  }

}
