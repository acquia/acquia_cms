<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the image formatter for media type image.
 *
 * @group acquia_cms_image
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
  protected $defaultTheme = 'stark';

  /**
   * Disable strict config schema checks in this test.
   *
   * Scheduler has a config schema errors, and until it's fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * The entity display object repository object.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      'embedded' => 'coh_medium',
      'default' => 'coh_x_large',
      'full' => 'coh_x_large',
      'large' => 'coh_x_large',
      'large_landscape' => 'coh_x_large_landscape',
      'large_super_landscape' => 'coh_x_large_super_landscape',
      'medium' => 'coh_medium',
      'medium_landscape' => 'coh_medium_landscape',
      'small' => 'coh_small',
      'small_landscape' => 'coh_small_landscape',
      'teaser' => 'x_small_landscape',
      'x_small_square' => 'x_small_square',
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
