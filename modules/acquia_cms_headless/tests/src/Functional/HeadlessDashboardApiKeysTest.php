<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests headless dashboard API key.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class HeadlessDashboardApiKeysTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_headless',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

  /**
   * Test  API Keys section exists.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSectionAvailable(): void {
    // Create admin user.
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();

    // Visit headless dashboard.
    $this->drupalGet("/admin/headless/dashboard");

    // Test API Keys section exists, get API Keys section.
    $consumers_fieldset = $assert_session->elementExists('css', '#edit-consumers-api-keys');

    // Test API Keys text exist.
    $this->assertEquals($consumers_fieldset->find('css', 'span')->getText(), 'API Keys');

    // Test create new consumer button exist.
    $this->assertEquals($consumers_fieldset->find('css', '.button')->getText(), 'Create new consumer');

    // Test create new consumer button link has destination.
    $buttonAction = $consumers_fieldset->find('css', '.button')->getAttribute('href');
    $this->assertEquals($buttonAction, '/admin/config/services/consumer/add?destination=/admin/headless/dashboard');

    // Test API Keys data table exist.
    $this->assertNotEmpty($consumers_fieldset->find('css', 'table'));

    // Test table header exist and has columns in same order.
    $this->assertEquals('Label', substr($consumers_fieldset->find('xpath', '//thead/tr/th[1]/a')->getText(), 0, 5));
    $this->assertEquals('Client ID', $consumers_fieldset->find('xpath', '//thead/tr/th[2]')->getText());
    $this->assertEquals('Secret', $consumers_fieldset->find('xpath', '//thead/tr/th[3]')->getText());
    $this->assertEquals('Operations', $consumers_fieldset->find('xpath', '//thead/tr/th[4]')->getText());

    // Test table body exist and has data in same order.
    $this->assertEquals('Default Consumer', $consumers_fieldset->find('xpath', '//tbody/tr/td[1]/a')->getText());
    // Test client ID exist and not empty.
    $this->assertNotEmpty($consumers_fieldset->find('xpath', '//tbody/tr/td[2]')->getText());
    $this->assertEquals('N/A', $consumers_fieldset->find('xpath', '//tbody/tr/td[3]')->getText());

    // Get the API Keys operations dropdown elements.
    $dropdown_list = $consumers_fieldset->findAll('css', 'ul li a');
    $this->assertCount(5, $dropdown_list);

    // Make sure all the operations exists in the select dropdown in order.
    $this->assertEquals('Edit', $consumers_fieldset->find('css', 'tbody > tr > td:nth-child(4) > div > div > ul > li.dropbutton-action:nth-child(1) > a')->getText());
    $consumers_fieldset->findButton('List additional actions')->click();
    $this->assertEquals('Generate New Secret', $consumers_fieldset->find('css', 'tbody > tr > td:nth-child(4) > div > div > ul > li.dropbutton-action:nth-child(3) > a')->getText());
    $this->assertEquals('Generate New Keys', $consumers_fieldset->find('css', 'tbody > tr > td:nth-child(4) > div > div > ul > li.dropbutton-action:nth-child(4) > a')->getText());
    $this->assertEquals('Delete', $consumers_fieldset->find('css', 'tbody > tr > td:nth-child(4) > div > div > ul > li.dropbutton-action:nth-child(5) > a')->getText());
    $this->assertEquals('Clone', $consumers_fieldset->find('css', 'tbody > tr > td:nth-child(4) > div > div > ul > li.dropbutton-action:nth-child(6) > a')->getText());

    // Click on Generate New Secret button.
    $this->assertSession()->elementExists('named', ['link', 'Generate New Secret'], $consumers_fieldset)->click();
    $consumer_modal = $this->assertSession()->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($consumer_modal);
    $this->assertEquals('Generate New Consumer Secret', $consumer_modal->find('css', '.ui-dialog-title')->getText());
    $this->assertNotEmpty($consumer_modal->find('css', '.headless-dashboard-modal'));
    $consumer_modal->find('css', '.ui-dialog-titlebar-close')->click();

    // Click Generate New Keys button.
    $this->assertSession()->elementExists('named', ['link', 'Generate New Keys'], $consumers_fieldset)->click();
    $keys_modal = $this->assertSession()->waitForElementVisible('css', '.ui-dialog');
    $this->assertNotEmpty($keys_modal);
    $this->assertEquals('Generate New API Keys', $keys_modal->find('css', '.ui-dialog-title')->getText());
    $keys_modal_content = $keys_modal->find('css', '.headless-dashboard-modal');
    $this->assertNotEmpty($keys_modal_content);
    $this->assertSession()->elementExists('named', ['link', 'Oauth Settings'], $keys_modal_content);
    $keys_modal->find('css', '.ui-dialog-titlebar-close')->click();

    // Click on Delete button.
    $consumers_fieldset->findButton('List additional actions')->click();
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $consumers_fieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $expected_url = $this->baseUrl . '/admin/config/services/consumer/1/delete?destination=/admin/headless/dashboard';
    $this->assertSame($expected_url, $this->getSession()->getCurrentUrl());
    $this->assertSession()->elementExists('named', ['link', 'Cancel'], $page)->click();

    // Click on Clone button.
    $consumers_fieldset->findButton('List additional actions')->click();
    $this->assertSession()->elementExists('named', ['link', 'Clone'], $consumers_fieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    $expected_url = $this->baseUrl . '/entity_clone/consumer/1?destination=/admin/headless/dashboard';
    $this->assertSame($expected_url, $this->getSession()->getCurrentUrl());
    $this->assertSession()->elementExists('named', ['button', 'Cancel'], $page)->click();
  }

}
