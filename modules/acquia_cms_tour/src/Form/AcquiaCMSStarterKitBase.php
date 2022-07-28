<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Dashboard form implementation.
 */
abstract class AcquiaCMSStarterKitBase extends FormBase implements AcquiaStarterKitInterface {

  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Module name which is responsible for this form.
   *
   * @var string
   */
  protected $form_name;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * Constructs a new AcquiaConnectorForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(
  StateInterface $state,
  LinkGeneratorInterface $link_generator,
  InfoParserInterface $info_parser) {
    $this->state = $state;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('link_generator'),
      $container->get('info_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationState() {
    $state = $this->state->get($this->getStateName(), FALSE);
    if (!$state) {
      $state = TRUE;
      $this->setConfigurationState();
    }
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateName() {
    return 'acms_' . $this->form_name . '_configured';
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationState($status = TRUE) {
    $this->state->set($this->getStateName(), $status);
  }

}
