<?php

namespace Drupal\Tests\multifield\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Multifield With Node Type with text & Number fields.
 *
 * @group Multifield
 */
class MultifieldUserTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'multifield',
    'field_ui'
  ];

  /**
   * Tests Multifield With all entities with text & Number fields.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $fields;
  protected $account;
  
  protected function setUp() {
    parent::setUp();
    $this->account = $this->drupalCreateUser([
      'administer permissions',
      'administer users',
      'administer user fields',
      'access content',
      'access administration pages',
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access administration pages',
    ]);
    $this->drupalLogin($this->account);
  }

  public function testCreateNewFields() {
    $this->drupalLogin($this->account);
    $this->create_new_multifields();
  }
  public function create_new_multifields(){
    $assert = $this->assertSession();
    $page=$this->getSession()->getPage();
    $this->drupalGet('admin/add-fields/user/user');
    $assert->statusCodeEquals(200);
    $fields=[
      [ "label" => "Test A",
        "new_storage_type"=>"string",      
      ],
      [ "label" => "Test B",
        "new_storage_type"=>"text_long",      
      ],
      [ "label" => "Test C",
        "new_storage_type"=>"text",      
      ],
      [ "label" => "Test D",
        "new_storage_type"=>"string_long",      
      ],
    ];
    $field_keys=array_keys($fields);
    $end_key=end($field_keys);
    // print_r($end_key);
    foreach ($fields as $key => $value) {
      if(isset($value['label']) && (isset($value['new_storage_type'])||isset($value['existing_storage_name']))){
        $new_value = strtolower($value['label']);
        $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
        $field_name=preg_replace('/_+/', '_', $new_value);
        $page->fillField("fields[$key][label]",$value['label']);
        $page->fillField("fields[$key][field_name]",$field_name);
        $page->selectFieldOption("fields[$key][new_storage_type]",$value['new_storage_type']);
        if ($end_key!=$key)
          $page->pressButton(t('Add another Field'));
      }
    }
    $page->pressButton(t("Save"));
    $counts=$end_key+1;
    if($counts==1)
      $message=$counts.t("Field is Created");
    elseif($counts>1)
      $message=$counts.t("Fields are Created");
    $assert->pageTextContains($message);
  }
}