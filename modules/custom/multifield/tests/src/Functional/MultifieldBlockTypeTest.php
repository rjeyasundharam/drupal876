<?php

namespace Drupal\Tests\multifield\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\WebTestBase;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests Multifield With Block Type with text & Number fields.
 *
 * @group Multifield
 */
class MultifieldBlockTypeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'block',
    'block_content',
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
      'administer blocks',
      'administer block_content fields',
      'access content',
      'access administration pages',
      'administer node fields',
      'administer node form display',
      'access administration pages',
    ]);
    $this->drupalLogin($this->account);
    $bundle = BlockContentType::create([
      'id' => "multifield_block_content",
      'label' => "Multifield Block Content",
      'revision' => FALSE,
    ]);
    $bundle->save();
    $bundle = BlockContentType::create([
      'id' => "multifield_block_content1",
      'label' => "Multifield Block Content1",
      'revision' => FALSE,
    ]);
    $bundle->save();
  }

  public function testCreateNewFields() {
    $this->drupalLogin($this->account);
    $this->create_new_multifields();
    $this->create_existing_fields();
  }
  public function create_new_multifields(){
    $assert = $this->assertSession();
    $page=$this->getSession()->getPage();
    $this->drupalGet('admin/add-fields/block_content/multifield_block_content');
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
    $this->drupalGet('admin/add-fields/block_content/multifield_block_content1');
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