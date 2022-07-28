<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsStarterKit;

use Drupal\acquia_cms_tour\Form\AcquiaCMSStarterKitBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Extension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsStarterKit(
 *   id = "acquia_cms_starter_kit_selection",
 *   label = @Translation("Starter Kit Selection"),
 *   weight = 1
 * )
 */
class StarterKitSelectionForm extends AcquiaCMSStarterKitBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $form_name = 'acquia_cms_starter_kit_selection';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $instance */
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'starter_kit_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Text input for Google Maps. ACMS can use the Gmaps API in two totally
    // different features (Site Studio and Place nodes). Site Studio is always
    // enabled in ACMS, but Place may not.
    // Initialize an empty array
    $service = \Drupal::service('acquia_cms_tour.starter_kit');
    $starter_kit = $this->state->get('acquia_cms.starter_kit');
    $missingModules = [
      'acquia_cms_enterprise_low_code' => $service->getMissingModules('acquia_cms_enterprise_low_code'),
      'acquia_cms_community' => $service->getMissingModules('acquia_cms_community'),
      'acquia_cms_headless' => $service->getMissingModules('acquia_cms_headless'),
    ];
    $defaultStarterKit = 'acquia_cms_community';
    if(!$missingModules['acquia_cms_enterprise_low_code']){
      $defaultStarterKit = 'defaultStarterKit';
    }
    $form_name = $this->form_name;
    $rows = [];
    $header = [
      'starter_kit' => t('Starter Kit'),
      'description' => t('Description'),
    ];
    $kits = [
      'Acquia CMS Enterprise low-code' => t('Acquia CMS with Site Studio and UIkit.'),
      'Acquia CMS Community' => t('Acquia CMS with required modules.'),
      'Acquia CMS Headless' => t('Acquia CMS with headless functionality.'),
    ];
    $starter_kit_options = [
      'acquia_cms_enterprise_low_code' => 'Acquia CMS Enterprise low-code',
      'acquia_cms_community' => 'Acquia CMS Community',
      'acquia_cms_headless' => 'Acquia CMS Headless'
    ];
   // Next, loop through the $kits array
   foreach ($kits as $kit => $description) {
     $rows[$kit] = [
       'starter_kit' => $kit,
       'description' => $description,
      ];
    }
    $form[$form_name] = [
      '#type' => 'details',
      '#title' => $this->t('Starter Kit Selection'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form[$form_name]['message'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t("Acquia CMS starter kits provide different starting points for your site depending on your requirements. Select from one of the starter kits below to enable the modules.") . '</p>',
    ];
    $form[$form_name]['message']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'tour-dashboard-table',
        ],
      ],
    ];
    $form[$form_name]['message']['starter_kit'] = [
      '#type' => 'select',
      '#options' => $starter_kit_options,
      '#default_value' => $this->state->get('acquia_cms.starter_kit') ?? $defaultStarterKit,
      '#prefix' => '<div class= "dashboard-fields-wrapper">',
      '#suffix' => "</div>",
    ];
    $message = "<div class='messages messages--error'><p>It seems that the
    following modules are missing from the codebase.</p> <p>We suggest running
    the below command to add the missing modules and visiting this page again.
    </p> <p><b style='font-size:1.2rem'>
    <i style='color:gray'>composer require -W ";
    if($missingModules['acquia_cms_enterprise_low_code']){
      $form[$form_name]['requirement_message_low_code'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($missingModules['acquia_cms_enterprise_low_code'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="starter_kit"]' => ['value' => 'acquia_cms_enterprise_low_code'],
            ],
          ],
      ];
    }
    if($missingModules['acquia_cms_community']){
      $form[$form_name]['requirement_message_community'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($missingModules['acquia_cms_community'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="starter_kit"]' => ['value' => 'acquia_cms_community'],
            ],
          ],
      ];
    }
    if($missingModules['acquia_cms_headless']){
      $form[$form_name]['requirement_message_hedless'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($missingModules['acquia_cms_headless'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="starter_kit"]' => ['value' => 'acquia_cms_headless'],
            ],
          ],
      ];
    }
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $starter_kit = $form_state->getValue(['starter_kit']);
    if ($starter_kit) {
      $this->state->set('hide_starter_kit_intro_dialog', TRUE);
      $this->state->set('acquia_cms.starter_kit', $starter_kit);
      $this->state->set('acquia_cms_tour_starter_kit_selection_progress', TRUE);
      $this->messenger()->addStatus('The configuration options have been saved.');
    }
    // Update state.
    $this->setConfigurationState();
  }

}
