<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_api_keys",
 *   label = @Translation("Acquia CMS Headless Consumer API Keys"),
 *   weight = 4
 * )
 */
class HeadlessApiKeys extends AcquiaCMSDashboardBase {
  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'consumers';

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($state, $module_handler, $link_generator, $info_parser);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_api_keys';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_cms_headless.settings'];
  }

  /**
   * Gets Entity data.
   *
   * @return array|int
   *   Returns an array of entity ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityData() {
    $header = $this->buildEntityHeader();
    $storage = $this->entityTypeManager->getStorage('consumer');
    $query = $storage->getQuery();
    $query->tableSort($header);
    $query->pager(2);

    return $query->execute();
  }

  /**
   * Build the form table header.
   *
   * @return array
   *   Returns an array with table header data.
   */
  public function buildEntityHeader(): array {
    return [
      'label' => [
        'data' => $this->t('Label'),
        'specifier' => 'label',
        'field' => 't.alpha',
        'sort' => 'asc',
      ],
      'client_id' => [
        'data' => $this->t('Client ID'),
        'specifier' => 'client_id',
      ],
      'secret' => [
        'data' => $this->t('Secret'),
        'specifier' => 'secret',
      ],
    ];
  }

  /**
   * Builds rows for the Form Table.
   *
   * @return array
   *   Returns an array of rows.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildEntityRows(): array {
    // @todo Add operations links.
    $rows = [];
    $consumer_data = $this->getEntityData();
    $storage = $this->entityTypeManager->getStorage('consumer');
    $consumers = $storage->loadMultiple($consumer_data);

    // Match the data with the columns.
    foreach ($consumers as $consumer) {
      $secret = $consumer->getTypedData()->get('secret')->getValue();
      $row = [
        'label' => $consumer->label(),
        'client_id' => $consumer->uuid(),
        // @todo Determine purpose of the secret here as only a hashed version
        // is accessible via $secret[0]['value'].  Placeholder set for now to
        // show which consumers have a secret and which don't.
        'secret' => !empty($secret) ? '**********' : 'N/A',
      ];

      $rows[$consumer->uuid()] = $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = FALSE;
    $module = $this->module . '_api_keys';
    $header = $this->buildEntityHeader();
    $rows = $this->buildEntityRows();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Keys'),
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
    ];

    $form[$module]['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No next.js sites currently exist.'),
    ];

    $form[$module]['pager'] = [
      '#type' => 'pager',
      '#element' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the config state.
    $this->setConfigurationState();
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setConfigurationState();
  }

  /**
   * {@inheritdoc}
   */
  public function checkMinConfiguration(): bool {
    return TRUE;
  }

}
