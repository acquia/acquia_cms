<?php

namespace Drupal\acquia_cms_image\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Parent class for Field API unit tests.
 */
class ImageFormatterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * The entity display object repository object.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityDisplay = $this->container->get('entity_display.repository');
  }

  /**
   * List of image style selected per view mode of entity media.
   *
   * @return string[]
   *   Returns an array of image style per view modes.
   */
  protected function viewImageStylefOfViewModes() {
    return [
      'embedded' => '',
      'large' => 'coh_large',
      'large_landscape' => 'coh_large_super_landscape',
      'medium' => 'coh_medium',
      'medium_landscape' => 'coh_medium_landscape',
      'small' => 'coh_small',
      'small_landscape' => 'coh_small_landscape',
    ];
  }

  /**
   * Test image style selected per view mode of media type image.
   */
  public function testImageViewDisplay() {
    foreach ($this->viewImageStylefOfViewModes() as $view_mode => $image_style) {
      $viewContent = $this->entityDisplay->getViewDisplay("media", "image", $view_mode)->get('content');
      $this->assertEquals($viewContent['image']['settings']['image_style'], $image_style);
    }
  }

}
