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
    // $this->assertSession()->statusCodeEquals(200);
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
    // @todo $keys_modal_content has link to redirect me to OAuth settings page.
    $this->assertSession()->elementExists('named', ['link', 'Oauth Settings'], $keys_modal_content);
    $keys_modal->find('css', '.ui-dialog-titlebar-close')->click();

    // Click on Delete button.
    $consumers_fieldset->findButton('List additional actions')->click();
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $consumers_fieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    // @todo getCurrentUrl() returns absolute path we need relative path.
    // $this->assertSame('/admin/config/services/consumer/1/delete?destination=/admin/headless/dashboard', $this->getSession()->getCurrentUrl());
    $this->assertSession()->elementExists('named', ['link', 'Cancel'], $page)->click();

    // Click on Clone button.
    $consumers_fieldset->findButton('List additional actions')->click();
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $consumers_fieldset)->click();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($page);
    // @todo getCurrentUrl() returns absolute path we need relative path.
    // $this->assertSame('/entity_clone/consumer/1?destination=/admin/headless/dashboard', $this->getSession()->getCurrentUrl());
    $this->assertSession()->elementExists('named', ['link', 'Cancel'], $page)->click();
  }

}
