<?php
// Use your module's testing namespace such as the one below.
namespace Drupal\Tests\multifield\ExistingSite;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use weitzman\DrupalTestTraits\ExistingSiteBase;
/**
 * A model test case using traits from Drupal Test Traits.
 */
class ExampleTest extends ExistingSiteBase
{
    /**
     * An example test method; note that Drupal API's and Mink are available.
     */
    public function testLlama()
    {
        // Creates a user. Will be automatically cleaned up at the end of the test.
        $author = $this->createUser([], null, true);
        // Create a taxonomy term. Will be automatically cleaned up at the end of the test.
        $vocab = Vocabulary::load('tags');
        $term = $this->createTerm($vocab);
        // Create a "Llama" article. Will be automatically cleaned up at end of test.
        $node = $this->createNode([
            'title' => 'Article',
            'type' => 'article',
            'field_tags' => [
                'target_id' => $term->id(),
            ],
            'uid' => $author->id(),
        ]);

        $node->setPublished()->save();
        $this->assertEquals($author->id(), $node->getOwnerId());
        // We can browse pages.
        $this->drupalGet($node->toUrl());
        $this->assertSession()->statusCodeEquals(200);
        $this->drupalGet($node->toUrl());
        // We can login and browse admin pages.
        $this->drupalLogin($author);
        $this->drupalGet($node->toUrl('edit-form'));

        $assert = $this->assertSession();
        $page=$this->getSession()->getPage();
        $this->drupalGet('admin/add-fields/node/article');
        $assert->statusCodeEquals(200);
        // $edit["fields[0][label]"]="Test A";
        // $page->fillField("entity_type_id","node");
        // $page->fillField("bundle","node");
        $field_machines=[
            'field_test_f',
            'field_test_i',
            'field_test_j',
            'field_test_k'
        ];
        foreach ($field_machines as $key => $value) {
            $field=\Drupal\field\Entity\FieldStorageConfig::loadByName('node', $value);
            if($field){
                $field->delete();   
            }
        }
        $fields=[
          [ "label" => "Test F",  
            "new_storage_type"=>"string",      
          ],
          [ "label" => "Test I",  
            "new_storage_type"=>"text_long",      
          ],
          [ "label" => "Test J",  
            "new_storage_type"=>"text",      
          ],
          [ "label" => "Test K",  
            "new_storage_type"=>"string_long",      
          ],
        ];
        $field_keys=array_keys($fields);
        $end_key=end($field_keys);
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