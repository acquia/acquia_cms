<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_api_users",
 *   label = @Translation("Acquia CMS Headless API Users"),
 *   weight = 6
 * )
 */
class HeadlessApiUsers extends AcquiaCmsDashboardBase {
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
   * Provides Starter Kit Next.js Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitNextjsService;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'consumers';

  /**
   * Provides headless role label.
   *
   * @var string
   */
  protected $headlessRoleLabel;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, EntityTypeManagerInterface $entity_type_manager, StarterkitNextjsService $starterkit_nextjs_service, ConfigFactoryInterface $config_factory, ?TypedConfigManagerInterface $typedConfigManager) {
    parent::__construct($state, $module_handler, $link_generator, $info_parser, $config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
    $this->headlessRoleLabel = $entity_type_manager->getStorage('user_role')->load('headless')->label();
    $this->starterKitNextjsService = $starterkit_nextjs_service;
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
      $container->get('acquia_cms_headless.starterkit_nextjs'),
      $container->get("config.factory"),
      $container->has('config.typed') ? $container->get('config.typed') : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_api_users';
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
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery();
    $query->condition('roles', 'headless');
    $query->range(0, 1);
    $query->tableSort($header);
    $query->pager(10);
    return $query->accessCheck(TRUE)->execute();
  }

  /**
   * Build the form table header.
   *
   * @return array
   *   Returns an array with table header data.
   */
  public function buildEntityHeader(): array {
    return [
      'name' => [
        'data' => $this->t('User Name'),
        'specifier' => 'name',
        'field' => 't.alpha',
        'sort' => 'asc',
      ],
      'role' => [
        'data' => $this->t('Roles'),
        'specifier' => 'role',
      ],
      'status' => [
        'data' => $this->t('Status'),
        'specifier' => 'status',
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
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Set an array of URI Relationships that will be used to build the
    // operations links.
    $operationLinks = [
      'view' => [
        'title' => $this->t('View'),
        'route' => 'canonical',
      ],
      'edit' => [
        'title' => $this->t('Edit'),
        'route' => 'edit-form',
      ],
      'clone' => [
        'title' => $this->t('Clone'),
        'route' => 'clone-form',
      ],
    ];
    foreach ($entities as $entity) {
      $operation = [];
      foreach ($operationLinks as $key => $operationLink) {
        $route_name = $entity->toUrl($operationLink['route'])->getRouteName();
        $operation[$key] = [
          'url' => Url::fromRoute($route_name, [$entityType => $entity->id()], $destination),
          'title' => $operationLink['title'],
        ];
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
   * @throws \Exception
   */
  public function buildEntityRows(): array {
    $rows = [];
    $entity_type = 'user';
    $user_data = $this->getEntityData();
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $users = $storage->loadMultiple($user_data);
    $operations = $this->createOperationLinks($entity_type);

    // Match the data with the columns.
    foreach ($users as $user) {
      if ($user->getTypedData()->get('status')->getValue()[0]['value']) {
        $status = 'status--active';
      }
      else {
        $status = 'status--inactive';
      }

      $row = [
        'name' => Link::fromTextAndUrl($user->label(), $operations[$user->id()]['edit']['url']),
        // Currently only displaying users assigned the headless role.
        'role' => $this->headlessRoleLabel,
        'status' => $this->t('<span class="@status"></span>', ['@status' => $status]),
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => $operations[$user->id()],
          ],
        ],
      ];

      $rows[$user->uuid()] = $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = FALSE;
    $module = $this->module . '_api_users';
    $header = $this->buildEntityHeader();
    $rows = $this->buildEntityRows();
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Users'),
      '#attributes' => [
        'class' => [],
      ],
    ];

    $form[$module]['info_text'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Only users assigned the <strong> @label </strong> user role will appear in this list.  If adding new Headless users, make sure to assign the <strong>Headless</strong> role.</p>', ['@label' => $this->headlessRoleLabel]),
      '#prefix' => '<div class="headless-dashboard-admin-heading"><div class="headless-dashboard-user-info">',
      '#suffix' => '</div>',
    ];

    $form[$module]['admin_links'] = [
      '#type' => 'link',
      '#title' => 'Add API User',
      '#url' => Url::fromRoute('user.admin_create', [], $destination),
      '#attributes' => [
        'class' => [
          'button',
          'button--action',
          'button--primary',
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-admin-links">',
      '#suffix' => '</div></div>',
    ];

    $form[$module]['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No next.js sites currently exist.'),
    ];

    $form[$module]['pager'] = [
      '#type' => 'pager',
      '#element' => 3,
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
