<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "google_tag",
 *   label = @Translation("Google TagManager"),
 *   weight = 5
 * )
 */
class GoogleTagManagerForm extends AcquiaCmsDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'google_tag';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_google_tag_manager_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_tag.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $configured = $this->getConfigurationState();
      if ($configured) {
        $form['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form[$module] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
      $accounts_wrapper_id = Html::getUniqueId('accounts-add-more-wrapper');
      $account_default_value = $this->config('google_tag.settings')->get('default_google_tag_entity');
      $form[$module]['accounts_wrapper'] = [
        '#type' => 'fieldset',
        '#prefix' => '<div class="dashboard-fields-wrapper remove-fieldset-boundary" id="' . $accounts_wrapper_id . '">
        Effortlessly configure and manage Google Tag Manager containers to
        seamlessly track application insights.',
        '#suffix' => '</div>',
      ];
      // Filter order (tabledrag).
      $form[$module]['accounts_wrapper']['accounts'] = [
        '#input' => FALSE,
        '#tree' => TRUE,
        '#type' => 'table',
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'account-order-weight',
          ],
        ],
      ];

      $accounts = $form_state->getValue('accounts', []);
      if ($accounts === []) {
        $config_name = 'google_tag.container.'. $account_default_value;
        $entity_accounts = $this->config($config_name)->get('tag_container_ids');
        foreach ($entity_accounts as $index => $account) {
          $accounts[$index]['value'] = $account;
          $accounts[$index]['weight'] = $index;
        }
        // Default fallback.
        if (count($accounts) === 0) {
          $accounts[] = ['value' => '', 'weight' => 0];
        }
      }

      foreach ($accounts as $index => $account) {
        $form[$module]['accounts_wrapper']['accounts'][$index]['#attributes']['class'][] = 'draggable';
        $form[$module]['accounts_wrapper']['accounts'][$index]['#weight'] = $account['weight'];
        $form[$module]['accounts_wrapper']['accounts'][$index]['value'] = [
          '#default_value' => (string) ($account['value'] ?? ''),
          '#maxlength' => 20,
          '#required' => (count($accounts) === 1),
          '#size' => 20,
          '#type' => 'textfield',
          '#pattern' => TagContainer::GOOGLE_TAG_MATCH,
          '#ajax' => [
            'callback' => [self::class, 'storeGtagAccountsCallback'],
            'disable-refocus' => TRUE,
            'event' => 'change',
            'wrapper' => 'advanced-settings-wrapper',
          ],
          '#attributes' => [
            'data-disable-refocus' => 'true',
          ],
        ];

        $form[$module]['accounts_wrapper']['accounts'][$index]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => (string) ($account['value'] ?? '')]),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => $index,
          '#parents' => ['accounts', $index, 'weight'],
          '#attributes' => ['class' => ['account-order-weight']],
        ];

        // If there is more than one id, add the remove button.
        if (count($accounts) > 1) {
          $form[$module]['accounts_wrapper']['accounts'][$index]['remove'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => 'remove_gtag_id_' . $index,
            '#parameter_index' => $index,
            '#limit_validation_errors' => [
              ['accounts'],
            ],
            '#submit' => [
              [self::class, 'removeGtagCallback'],
            ],
            '#ajax' => [
              'callback' => [self::class, 'gtagFormCallback'],
              'wrapper' => $form['#id'],
            ],
          ];
        }
      }

      $id_prefix = implode('-', ['accounts_wrapper', 'accounts']);
      // Add blank account.
      $form[$module]['accounts_wrapper']['add_gtag_id'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another ID'),
        '#name' => str_replace('-', '_', $id_prefix) . '_add_gtag_id',
        '#submit' => [
          [self::class, 'addGtagCallback'],
        ],
        '#ajax' => [
          'callback' => [self::class, 'ajaxRefreshAccounts'],
          'wrapper' => $accounts_wrapper_id,
          'effect' => 'fade',
        ],
      ];

      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#limit_validation_errors' => [],
        '#submit' => ['::ignoreConfig'],
      ];
      $form[$module]['actions']['advanced'] = [
        '#prefix' => '<div class= "dashboard-tooltiptext">',
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('entity.google_tag_container.single_form')
        ),
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['advanced']['information'] = [
        '#prefix' => '<b class= "tool-tip__icon">i',
        '#suffix' => "</b>",
      ];
      $form[$module]['actions']['advanced']['tooltip-text'] = [
        '#prefix' => '<span class= "tooltip">',
        '#markup' => $this->t("Opens Advance Configuration in new tab"),
        '#suffix' => "</span></div>",
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tag_container_ids = [];
    $default_id = '';
    $account_default_value = $this->config('google_tag.settings')->get('default_google_tag_entity');
    $config_name = 'google_tag.container.'. $account_default_value;
    foreach ($form_state->getValue('accounts') as $account) {
      if (!$default_id) {
        $default_id = $account['value'];
      }
      $tag_container_ids[$account['weight']] = $account['value'];
    }
    // Need to save tags without weights otherwise it doesn't show up on UI.
    $this->configFactory->getEditable($config_name)->set('tag_container_ids', array_values($tag_container_ids))->save();
    if ($this->config($config_name)->get('id') === NULL) {
      // Set the ID and Label based on the first Google Tag.
      $config_id = uniqid($default_id . '.', TRUE);
      $this->configFactory->getEditable($config_name)->set('id', $config_id);
      $this->configFactory->getEditable($config_name)->set('label', $default_id);
    }
    $this->setConfigurationState();
    $this->messenger()->addStatus('The configuration options have been saved.');
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
    $uri = $this->config('google_tag.settings')->get('uri');
    return (bool) $uri;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function storeGtagAccountsCallback(array &$form, FormStateInterface $form_state) {
    // Update Advanced Settings Form.
    return $form['google_tag']['accounts_wrapper'];
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeGtagCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = $triggering_element['#parameter_index'];
    $accounts = $form_state->getValue('accounts', []);
    unset($accounts[$index]);
    $form_state->setValue('accounts', $accounts);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax account buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function gtagFormCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addGtagCallback(array &$form, FormStateInterface $form_state) {
    $accounts = $form_state->getValue('accounts', []);
    $accounts[] = [
      'value' => '',
      'weight' => count($accounts),
    ];
    $form_state->setValue('accounts', $accounts);
    $form_state->setRebuild();
  }

  /**
   * Callback for add more gtag accounts.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Accounts wrapper.
   */
  public static function ajaxRefreshAccounts(array $form, FormStateInterface $form_state) {
    return $form['google_tag']['accounts_wrapper'];
  }

}
