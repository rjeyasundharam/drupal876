<?php

namespace Drupal\Tests\multifield\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\WebTestBase;
/**
 * Tests Multifield form with only label value.
 *
 * @group Multifield
 */
class MultifieldFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'multifield',
    'field_ui'
  ];

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $fields;

  protected $adminUser;
  protected $authUser;

  public static function getInfo() {
    return array(
      'name' => t('Multifield test'),
      'description' => t('Tests Multifield form with only label value'),
      'group' => t('Multifield')
    );
  }

  protected function setUp() {
    parent::setUp();
  }

  public function testInputCheck() {
     $account = $this->drupalCreateUser([
      'access content',
      'administer users',
      'access administration pages',
      'administer permissions',
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node form display',
    ]);
    $this->drupalLogin($account);
    
    $assert = $this->assertSession();
    $page=$this->getSession()->getPage();
    $this->drupalGet('admin/add-fields/node/article');
    $assert->statusCodeEquals(200);
    $edit["fields[0][label]"]="Test A";
    $page->fillField("fields[0][label]","Test A");
    $page->fillField("fields[0][field_name]","test_a");
    $page->pressButton("op");
    $assert->pageTextContains("You need to select a field type or an existing field.");
    $this->drupalLogout();
  }
}