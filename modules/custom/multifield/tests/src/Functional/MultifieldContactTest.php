<?php

namespace Drupal\Tests\multifield\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\WebTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\comment\CommentInterface;

/**
 * Tests Multifield With Node Type with text & Number fields.
 *
 * @group Multifield
 */
class MultifieldContactTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'contact',
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
      'access content',
      'administer users',
      'access administration pages',
      'administer permissions',
      'administer content types',
      'administer contact forms',
      'administer account settings',
      'administer contact_message fields',
    ]);
    $this->drupalLogin($this->account);
    // Create Content Type for Multiple Test.

    $edit = [];
    $edit['label'] = "Multifield Test";
    $edit['id'] = "multifield_test_form";
    $edit['message'] = t("Message has been sent.");
    $edit['recipients'] = "admin@example.com";
    $edit['reply'] = "";
    $edit['selected'] = TRUE;
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));
    $edit['label'] = "Multifield Test1";
    $edit['id'] = "multifield_test_form1";
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));
  }

  public function testCreateNewFields() {
    $this->drupalLogin($this->account);
    $this->create_new_multifields();
    $this->create_existing_fields();
  }
  public function create_new_multifields(){
    $assert = $this->assertSession();
    $page=$this->getSession()->getPage();
    $this->drupalGet('admin/add-fields/contact_message/multifield_test_form');
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

  public function create_existing_fields() {
    
    $assert = $this->assertSession();
    $page=$this->getSession()->getPage();
    $this->drupalGet('admin/add-fields/contact_message/multifield_test_form1');
    $assert->statusCodeEquals(200);
    $fields=[
      [ "label" => "A Text Field",  
        "existing_storage_name"=>"field_test_a",      
      ],
      [ "label" => "B Text Field",  
        "existing_storage_name"=>"field_test_b",      
      ],
      [ "label" => "C Text Field",  
        "existing_storage_name"=>"field_test_c",      
      ],
      [ "label" => "F Text Field",  
        "new_storage_type"=>"string_long",      
      ]
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
        if(isset($value['existing_storage_name'])){
          $page->selectFieldOption("fields[$key][existing_storage_name]",$value["existing_storage_name"]);
        }
        elseif(isset($value['new_storage_type'])){
          $page->selectFieldOption("fields[$key][new_storage_type]",$value['new_storage_type']);
        }
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