<?php

namespace Drupal\acquia_cms_common\ProxyClass;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module uninstall validator for acquia cms modules proxy class.
 */
class AcmsModulesUninstallValidator implements ModuleUninstallValidatorInterface {

  use DependencySerializationTrait;

  /**
   * The id of the original proxy service.
   *
   * @var string
   */
  protected $drupalProxyServiceId;

  /**
   * The real proxy service, after it was lazy loaded.
   *
   * @var \Drupal\acquia_cms_common\AcmsModulesUninstallValidator
   */
  protected $service;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a ProxyClass Drupal proxy object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param string $drupal_proxy_service_id
   *   The service ID of the proxy service.
   */
  public function __construct(ContainerInterface $container, string $drupal_proxy_service_id) {
    $this->container = $container;
    $this->drupalProxyServiceId = $drupal_proxy_service_id;
  }

  /**
   * Lazy loads proxy service from the container.
   *
   * @return object
   *   Returns the constructed proxy service.
   */
  protected function lazyLoadService() {
    if (!isset($this->service)) {
      $this->service = $this->container->get($this->drupalProxyServiceId);
    }
    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    return $this->lazyLoadService()->validate($module);
  }

  /**
   * {@inheritdoc}
   */
  public function setStringTranslation(TranslationInterface $translation) {
    return $this->lazyLoadService()->setStringTranslation($translation);
  }

}
