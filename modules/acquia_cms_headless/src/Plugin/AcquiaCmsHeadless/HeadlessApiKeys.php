<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
class HeadlessApiKeys extends AcquiaCmsDashboardBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->starterKitNextjsService = $container->get('acquia_cms_headless.starterkit_nextjs');

    return $instance;
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
      'operations' => $this->t('Operations'),
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
      'edit' => [
        'title' => $this->t('Edit'),
        'route' => 'edit-form',
      ],
      'consumer_secret' => [
        'title' => $this->t('Generate New Secret'),
        'route' => 'acquia_cms_headless.generate_consumer_secret',
      ],
      'consumer_keys' => [
        'title' => $this->t('Generate New Keys'),
        'route' => 'acquia_cms_headless.generate_keys',
      ],
      'delete' => [
        'title' => $this->t('Delete'),
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
        if (in_array($key, ['consumer_secret', 'consumer_keys'])) {
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
          // Check link templete.
          if ($entity->hasLinkTemplate($operationLink['route'])) {
            $route_name = $entity->toUrl($operationLink['route'])->getRouteName();
            $operation[$key] = [
              'url' => Url::fromRoute($route_name, ['consumer' => $entity->id()], $destination),
              'title' => $operationLink['title'],
            ];
          }
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
    /** @var \Drupal\Core\Entity\EntityStorageInterface $consumers */
    $consumers = $storage->loadMultiple($consumer_data);
    $operations = $this->createOperationLinks($entity_type);

    // Match the data with the columns.
    foreach ($consumers as $consumer) {
      $secret = $consumer->getTypedData()->get('secret')->getValue();
      $row = [
        'label' => Link::fromTextAndUrl($consumer->label(), $operations[$consumer->id()]['edit']['url']),
        'client_id' => $consumer->getClientId(),
        'secret' => !empty($secret) ? '**********' : 'N/A',
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => $operations[$consumer->id()],
          ],
        ],
      ];

      $rows[$consumer->getClientId()] = $row;
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

    // Set the destination query array.
    $destination = $this->starterKitNextjsService->dashboardDestination();

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

    $form[$module]['info_text'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Consumer secrets are encrypted and cannot be displayed. Reset a consumer secret to obtain a new known value.</p>'),
      '#prefix' => '<div class="headless-dashboard-admin-heading"><div class="headless-dashboard-user-info">',
      '#suffix' => '</div>',
    ];

    $form[$module]['admin_links'] = [
      '#type' => 'link',
      '#title' => 'Create new consumer',
      '#url' => Url::fromRoute('entity.consumer.add_form', [], $destination),
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
