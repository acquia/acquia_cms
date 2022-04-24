<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_headless\Service\RobustApiService;
use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
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
   * Provides Robust API Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\RobustApiService
   */
  protected $robustApiService;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'consumers';

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, EntityTypeManagerInterface $entity_type_manager, RobustApiService $robust_api_serivce) {
    parent::__construct($state, $module_handler, $link_generator, $info_parser);
    $this->entityTypeManager = $entity_type_manager;
    $this->robustApiService = $robust_api_serivce;
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
      $container->get('entity_type.manager'),
      $container->get('acquia_cms_headless.robustapi')
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
      $this->t('Operations'),
    ];
  }

  /**
   * A function that builds an array of entity operation links.
   *
   * @param string $entityType
   *   Accepts an entity type id string value.
   *
   * @return array
   *   Return an array of operation links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function createOperationLinks(string $entityType): array {
    // Initialize the array that we will eventually return.
    $operations = [];

    // Set some service variables.
    $user_data = $this->getEntityData();
    $storage = $this->entityTypeManager;
    $entityStorage = $storage->getStorage($entityType);
    $entities = $entityStorage->loadMultiple($user_data);
    $destination = $this->robustApiService->dashboardDestination();

    // Set an array of URI Relationships that will be used to build the
    // operations links.
    $operationLinks = [
      'consumer_secret' => [
        'title' => $this->t('New Secret'),
        'route' => 'acquia_cms_headless.generate_consumer_secret',
      ],
      'edit' => [
        'title' => $this->t('Edit'),
        'route' => 'edit-form',
      ],
      'delete' => [
        'title' => $this->t('Title'),
        'route' => 'delete-form',
      ],
      'clone' => [
        'title' => $this->t('Clone'),
        'route' => 'clone-form',
      ],
    ];

    foreach ($entities as $entity) {
      $operation = [];
      foreach ($operationLinks as $key => $operationLink) {
        if ($key == 'consumer_secret') {
          $operation[$key] = [
            'url' => Url::fromRoute($operationLink['route'], [$entityType => $entity->id()], $destination),
            'title' => $operationLink['title'],
            'attributes' => [
              'class' => [
                'use-ajax',
              ],
              'data-dialog-options' => Json::encode([
                'minHeight' => 400,
                'width' => 912,
              ]),
              'data-dialog-type' => 'modal',
              'data-ajax-progress' => "fullscreen",
            ],
          ];
        }
        else {
          $route_name = $entity->toUrl($operationLink['route'])->getRouteName();
          $operation[$key] = [
            'url' => Url::fromRoute($route_name, ['consumer' => $entity->id()], $destination),
            'title' => $operationLink['title'],
          ];
        }
      }

      $operations[$entity->id()] = $operation;
    }

    return $operations;
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
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildEntityRows(): array {
    $rows = [];
    $entity_type = 'consumer';
    $consumer_data = $this->getEntityData();
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $consumers = $storage->loadMultiple($consumer_data);
    $operations = $this->createOperationLinks($entity_type);

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
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => $operations[$consumer->id()],
          ],
        ],
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
        'class' => [],
      ],
    ];

    $form[$module]['admin_links'] = [
      '#type' => 'link',
      '#title' => 'Generate New API Keys',
      '#url' => Url::fromRoute('acquia_cms_headless.generate_keys'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button--action',
          'button--primary',
        ],
        'data-dialog-options' => Json::encode([
          'minHeight' => 400,
          'width' => 912,
        ]),
        'data-dialog-type' => 'modal',
        'data-ajax-progress' => "fullscreen",
      ],
      '#prefix' => '<div class="headless-dashboard-admin-links">',
      '#suffix' => '</div>',
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
