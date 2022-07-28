<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsStarterKit;

use Drupal\acquia_cms_tour\Form\AcquiaCMSStarterKitBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\geocoder\GeocoderProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsStarterKit(
 *   id = "acquia_cms_starter_kit_config",
 *   label = @Translation("Extend Starter Kit"),
 *   weight = 2
 * )
 */
class StarterKitConfigForm extends AcquiaCMSStarterKitBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $form_name = 'acquia_cms_starter_kit_config';

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
    return 'starter_kit_configure_form';
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
    $starterKit = [
      'acquia_cms_demo_content' => $service->getMissingModules($starter_kit, 'Yes','No'),
      'acquia_cms_content_model' => $service->getMissingModules($starter_kit, 'No', 'Yes'),
      'acquia_cms_starter_kit_only' => $service->getMissingModules($starter_kit, 'No', 'No'),
    ];
    $form_name = $this->form_name;
    $form[$form_name] = [
      '#type' => 'details',
      '#title' => $this->t('Extend Starter Kit'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form[$form_name]['demo'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to enable demo content?'),
      '#options' => ['none' => 'Please select', 'No' => 'No', 'Yes' => 'Yes'],
    ];
    $form[$form_name]['content_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to enable the content model?'),
      '#options' => ['none' => 'Please select', 'No' => 'No', 'Yes' => 'Yes'],
      '#states' => [
        'visible' => [
          ':input[name="demo"]' => ['value' => 'No'],
        ],
      ],
    ];
    $form[$form_name]['declaration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I am aware that I can not change starter kit once selected.'),
      '#required' => TRUE,
      '#prefix' => '<div class= "dashboard-fields-wrapper">',
      '#suffix' => "</div>",
    ];
    $message = "<div class='messages messages--error'><p>It seems that the
    following modules are missing from the codebase.</p> <p>We suggest running
    the below command to add the missing modules and visiting this page again.
    </p> <p><b style='font-size:1.2rem'>
    <i style='color:gray'>composer require -W ";
    if($starterKit['acquia_cms_demo_content']){
      $form[$form_name]['requirement_message_demo_content'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($starterKit['acquia_cms_demo_content'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="demo"]' => ['value' => 'Yes'],
              ':input[name="content_model"]' => ['value' => 'Yes'],
            ],
          ],
      ];
    }
    if($starterKit['acquia_cms_demo_content']){
      $form[$form_name]['requirement_message_demo_no_content_model'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($starterKit['acquia_cms_demo_content'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="demo"]' => ['value' => 'Yes'],
              ':input[name="content_model"]' => ['!value' => 'Yes'],
            ],
          ],
      ];
    }
    if($starterKit['acquia_cms_content_model']){
      $form[$form_name]['requirement_message_content_model'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($starterKit['acquia_cms_content_model'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="demo"]' => ['!value' => 'Yes'],
              ':input[name="content_model"]' => ['value' => 'Yes'],
            ],
          ],
      ];
    }
    if($starterKit['acquia_cms_starter_kit_only']){
      $form[$form_name]['requirement_message_starter_kit_only'] = [
          '#type' => 'item',
          '#markup' => $this->t($message . "{$service->getMissingModulesCommand($starterKit['acquia_cms_starter_kit_only'])}" . '</i></b></p></div>'),
          '#states' => [
            'visible' => [
              ':input[name="demo"]' => ['!value' => 'Yes'],
              ':input[name="content_model"]' => ['!value' => 'Yes'],
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
    $starter_kit_demo = $form_state->getValue(['demo']) ?? 'No';
    $starter_kit_content_model = $form_state->getValue(['content_model']) ?? 'No';
    if ($starter_kit_demo && $starter_kit_content_model) {
      $this->state->set('acquia_cms.starter_kit_demo', $starter_kit_demo);
      $this->state->set('acquia_cms.starter_kit_content_model', $starter_kit_content_model);
      $this->state->set('acquia_cms_tour_staretr_kit_demo_progress', TRUE);
      $this->messenger()->addStatus('The configuration options have been saved.');
    }
    $starter_kit = $this->state->get('acquia_cms.starter_kit');
    $service = \Drupal::service('acquia_cms_tour.starter_kit');
    $missingModules = $service->getMissingModules($starter_kit, $starter_kit_demo, $starter_kit_content_model);
    if(!$missingModules){
      $this->state->set('show_starter_kit_modal', FALSE);
      $this->state->set('starter_kit_wizard_completed', TRUE);
      $service->enableModules($starter_kit, $starter_kit_demo, $starter_kit_content_model);
      $this->messenger()->addStatus('The required starter kit has been installed. Also, the related modules & themes have been enabled.');
    }
    else{
      $this->messenger()->addStatus("It seems that the following modules are missing from the codebase. We suggest running the below command to add the missing modules and visiting this page again. Use 'composer require -W {$missingModules}'");
    }
    // Update state.
    $this->setConfigurationState();
  }

}
