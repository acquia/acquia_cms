<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

/**
 * Tests headless dashboard API key.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class HeadlessDashboardApiKeysTest extends HeadlessDashboardTestBase {

  /**
   * Test  API Keys section exists.
   */
  public function testSectionAvailable(): void {
    // Create admn user.
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();

    // Visit headless dashboard.
    $this->drupalGet("/admin/headless/dashboard");
    $this->assertSession()->statusCodeEquals(200);

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
    $this->assertEquals('Label', $consumers_fieldset->find('xpath', '//thead/tr/th[1]/a/text()'));
    $this->assertEquals('Client ID', $consumers_fieldset->find('xpath', '//thead/tr/th[2]')->getText());
    $this->assertEquals('Secret', $consumers_fieldset->find('xpath', '//thead/tr/th[3]')->getText());
    $this->assertEquals('Operations', $consumers_fieldset->find('xpath', '//thead/tr/th[4]')->getText());

    // Test table body exist and has data in same order.
    $this->assertEquals('Default Consumer', $consumers_fieldset->find('xpath', '//tbody/tr/td[1]/a')->getText());
    // Test client ID exist and not empty.
    $this->assertNotEmpty($consumers_fieldset->find('xpath', '//tbody/tr/td[2]')->getText());
    $this->assertEquals('N/A', $consumers_fieldset->find('xpath', '//tbody/tr/td[3]')->getText());

    // Get the API Keys operations dropdown elements.
    $operations_dropdown = [
      'Edit',
      'Generate New Secret',
      'Generate New Keys',
      'Delete',
      'Clone',
    ];
    $dropdown_list = $consumers_fieldset->findAll('css', 'ul li');
    $this->assertCount(5, $dropdown_list);
    // print_r($dropdown_list);die;
    // Make sure all the operations exists in the select dropdown in order.
    foreach ($dropdown_list as $key => $list) {
      $this->assertEquals($list->getAttribute('value'), $operations_dropdown[$key]);
    }

  }

}
