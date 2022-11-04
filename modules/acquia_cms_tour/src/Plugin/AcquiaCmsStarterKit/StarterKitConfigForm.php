<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsStarterKit;

use Drupal\acquia_cms_tour\Form\AcquiaCmsStarterKitBase;
use Drupal\acquia_cms_tour\Services\StarterKitService;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
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
class StarterKitConfigForm extends AcquiaCmsStarterKitBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $formName = 'acquia_cms_starter_kit_config';

  /**
   * Starterkit service.
   *
   * @var Drupal\acquia_cms_tour\Services\StarterKitService
   */
  protected StarterKitService $starterKitService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->starterKitService = $container->get('acquia_cms_tour.starter_kit');

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
    // Initialize an empty array.
    $starter_kit = $this->state->get('acquia_cms.starter_kit');
    $starterKit = [
      'acquia_cms_demo_content' => $this->starterKitService->getMissingModules($starter_kit, 'Yes', 'No'),
      'acquia_cms_content_model' => $this->starterKitService->getMissingModules($starter_kit, 'No', 'Yes'),
      'acquia_cms_starter_kit_only' => $this->starterKitService->getMissingModules($starter_kit, 'No', 'No'),
    ];
    $formName = $this->formName;
    $form[$formName] = [
      '#type' => 'details',
      '#title' => $this->t('Extend Starter Kit'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form[$formName]['demo'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to enable demo content?'),
      '#options' => [
        'none' => $this->t('Please select'),
        'No' => $this->t('No'),
        'Yes' => $this->t('Yes'),
      ],
    ];
    $form[$formName]['content_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to enable the content model?'),
      '#options' => [
        'none' => $this->t('Please select'),
        'No' => $this->t('No'),
        'Yes' => $this->t('Yes'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="demo"]' => ['value' => 'No'],
        ],
      ],
    ];
    $form[$formName]['declaration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I am aware that I can not change starter kit once selected.'),
      '#required' => TRUE,
      '#prefix' => '<div class= "dashboard-fields-wrapper">',
      '#suffix' => "</div>",
    ];
    $formattedMessage = new FormattableMarkup(
      '<div class="messages messages--error">
        <p>@message1</p>
        <p>@message2</p>
        <p><b style="font-size:1.2rem"><i style="color:gray"> @message3',
      [
        '@message1' => 'It seems that thefollowing modules are missing from the codebase',
        '@message2' => 'We suggest running the below command to add the missing modules and visiting this page again.',
        '@message3' => 'composer require -W ',
      ]
    );
    if ($starterKit['acquia_cms_demo_content']) {
      $message = new FormattableMarkup(
        '@message @missingModules </i></b></p></div>',
        [
          '@message' => $formattedMessage,
          '@missingModules' => $this->starterKitService->getMissingModulesCommand($starterKit['acquia_cms_demo_content']),
        ]
      );
      $form[$formName]['requirement_message_demo_content'] = [
        '#type' => 'item',
        '#markup' => $this->t('@message', ['@message' => $message]),
        '#states' => [
          'visible' => [
            ':input[name="demo"]' => ['value' => 'Yes'],
            ':input[name="content_model"]' => ['value' => 'Yes'],
          ],
        ],
      ];
    }
    if ($starterKit['acquia_cms_demo_content']) {
      $message = new FormattableMarkup(
        '@message @missingModules </i></b></p></div>',
        [
          '@message' => $formattedMessage,
          '@missingModules' => $this->starterKitService->getMissingModulesCommand($starterKit['acquia_cms_demo_content']),
        ]
      );
      $form[$formName]['requirement_message_demo_no_content_model'] = [
        '#type' => 'item',
        '#markup' => $this->t('@message', ['@message' => $message]),
        '#states' => [
          'visible' => [
            ':input[name="demo"]' => ['value' => 'Yes'],
            ':input[name="content_model"]' => ['!value' => 'Yes'],
          ],
        ],
      ];
    }
    if ($starterKit['acquia_cms_content_model']) {
      $message = new FormattableMarkup(
        '@message @missingModules </i></b></p></div>',
        [
          '@message' => $formattedMessage,
          '@missingModules' => $this->starterKitService->getMissingModulesCommand($starterKit['acquia_cms_content_model']),
        ]
      );
      $form[$formName]['requirement_message_content_model'] = [
        '#type' => 'item',
        '#markup' => $this->t('@message', ['@message' => $message]),
        '#states' => [
          'visible' => [
            ':input[name="demo"]' => ['!value' => 'Yes'],
            ':input[name="content_model"]' => ['value' => 'Yes'],
          ],
        ],
      ];
    }
    if ($starterKit['acquia_cms_starter_kit_only']) {
      $message = new FormattableMarkup(
        '@message @missingModules </i></b></p></div>',
        [
          '@message' => $formattedMessage,
          '@missingModules' => $this->starterKitService->getMissingModulesCommand($starterKit['acquia_cms_starter_kit_only']),
        ]
      );
      $form[$formName]['requirement_message_starter_kit_only'] = [
        '#type' => 'item',
        '#markup' => $this->t('@message', ['@message' => $message]),
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
    $starter_kits = [
      'acquia_cms_enterprise_low_code' => 'Acquia CMS Enterprise low-code',
      'acquia_cms_community' => 'Acquia CMS Community',
      'acquia_cms_headless' => 'Acquia CMS Headless',
    ];
    $starter_kit_demo = $form_state->getValue(['demo']) ?? 'No';
    $starter_kit_content_model = $form_state->getValue(['content_model']) ?? 'No';
    if ($starter_kit_demo && $starter_kit_content_model) {
      $this->state->set('acquia_cms.starter_kit_demo', $starter_kit_demo);
      $this->state->set('acquia_cms.starter_kit_content_model', $starter_kit_content_model);
      $this->state->set('acquia_cms_tour_staretr_kit_demo_progress', TRUE);
      $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
    }
    $starter_kit = $this->state->get('acquia_cms.starter_kit');
    $missing_modules = $this->starterKitService->getMissingModules($starter_kit, $starter_kit_demo, $starter_kit_content_model);
    if (!$missing_modules) {
      $this->state->set('show_starter_kit_modal', FALSE);
      $this->state->set('starter_kit_wizard_completed', TRUE);
      $this->starterKitService->enableModules($starter_kit, $starter_kit_demo, $starter_kit_content_model);
      $this->messenger()->addStatus($this->t('The %starter_kit starter kit has been installed. Also, the related modules & themes have been enabled.', [
        '%starter_kit' => $starter_kits[$starter_kit],
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t("It seems that the following modules are missing from the codebase. We suggest running the below command to add the missing modules and visiting this page again. Use 'composer require -W %missing_modules'", [
        '%missing_modules' => $missing_modules,
      ]));
    }
    // Update state.
    $this->setConfigurationState();
  }

}
