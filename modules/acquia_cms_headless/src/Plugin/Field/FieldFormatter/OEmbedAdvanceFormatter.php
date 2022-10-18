<?php

namespace Drupal\acquia_cms_headless\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'oembed_advance' formatter.
 *
 * @FieldFormatter(
 *   id = "oembed_advance",
 *   label = @Translation("oEmbed Advance content"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class OEmbedAdvanceFormatter extends OEmbedFormatter {

  /**
   * The request_stack service object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper, Request $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if (!$this->config->get("iframe_domain")) {
      $host = $this->requestStack->getSchemeAndHttpHost();
      foreach ($elements as &$element) {
        if (isset($element["#tag"]) && $element["#tag"] == "iframe") {
          $src = $element["#attributes"]["src"] ?? "";
          // Check if iframe source starts with `/`,
          // that means the iframe_domain is not set.
          // So add the current site domain as the fallback.
          if ($src && $src[0] == "/") {
            $element["#attributes"]["src"] = $host . $src;
          }
        }
      }
    }
    return $elements;
  }

}
