<?php

namespace Drupal\multifield\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldStorageConfigInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class MultifieldForm extends FormBase {
  protected $entityTypeId;
  protected $fields=1;
  protected $field_type_options = [];
  protected $bundle;
  protected $plugin_definitions;
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multifield_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle="page",$entity_type_id = "node") {
    // Initial number of names.
    // $field_storage = FieldStorageConfig::loadByName('node', 'field_test_a')->delete();
    if(is_object($bundle))
      $bundle=$bundle->get("type");

    $form['#tree'] = TRUE;
    $form['step'] = [
      '#type' => 'value',
      '#value' => !empty($form_state->getValue('step')) ? $form_state->getValue('step') : 1,
    ];
    $form['entity_type_id'] = [
      '#type' => 'value',
      '#value' => $entity_type_id,
    ];
    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $bundle,
    ];

    $form_state->set('entity_type_id', $entity_type_id);
    $form_state->set('bundle', $bundle);
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $form_state->get('bundle');
    $type = \Drupal::service('plugin.manager.field.field_type');
    $this->plugin_definitions = $type->getDefinitions();
    // Gather valid field types.
    
    foreach ($type->getGroupedDefinitions($this->plugin_definitions) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $this->field_type_options[$category][$name] = $field_type['label'];
      }
    }
    $container='container-'.$this->fields;
    

    // Field label and field_name.
    $form['fields']=[
      '#type' => 'container',
      '#attributes' => ['id' => 'multifieldset'],
      "#tree" => TRUE,
    ];

    $form['fields']=$this->getContainer($form['step']['#value'],$form_state);

    // Button to add more names.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['addfield'] = [
      '#type' => 'submit',
      '#submit' => ['::addNewFields'],
      '#ajax' => [
        'wrapper' => 'multifield-wrapper',
        'callback' => '::prompt',
      ],
      '#value' => $this->t('Add another Field'),
    ];

    $form['#prefix'] = '<div id="multifield-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'multifield/multifield_layout';

    return $form;
  }

/**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fields=$values['fields'];
    foreach ($fields as $key => $value) {
      if (!isset($value['new_storage_type']) && !isset($value['existing_storage_name'])) {
        if($value['new_storage_type']=="" && $value['new_storage_type']=="")
        $form_state->setErrorByName("fields[$key][new_storage_type]", $this->t('You need to select a field type or an existing field.'));
      }
      elseif (isset($value['new_storage_type']) && isset($value['existing_storage_name'])) {
        if($value['new_storage_type']!="" && $value['existing_storage_name']!="")
        $form_state->setErrorByName("fields[$key][new_storage_type]", $this->t('Adding a new field and re-using an existing field at the same time is not allowed.'));
      }
    }
  }

  protected function validateAddNew(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fields=$values['fields'];
    $cerrors=0;
    foreach ($fields as $key => $value) {
      if ($value['label']) {
        $form_state->setErrorByName("fields[$key][label]", $this->t('Add new field: you need to provide a label.'));
      }
      if($value['new_storage_type']){
        if ($value['field_name']) {
          $form_state->setErrorByName("fields[$key][field_name]", $this->t('Add new field: you need to provide a machine name for the field.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = [];
    // dpm($form);
    switch ($values['op']) {
      case 'Add another Field':
        $this->addNewFields($form, $form_state);
        break;

      default:
        $this->finalSubmit($form, $form_state);
    }
    
  }
  public function finalSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fields=$values['fields'];
    $type=$form_state->get('entity_type_id');
    $bundle=$form_state->get('bundle');
    $field_prefix=\Drupal::config('field_ui.settings')->get('field_prefix');
    $entity_type=\Drupal::entityManager()->getDefinition($type);
    // dpm($entity_type);
    $param_key=$entity_type->getBundleEntityType();
    $fcount=0;
    foreach ($fields as $key => $value) {
      if($value['remove']==FALSE){
        if(isset($value['existing_storage_name'])&&$value['existing_storage_name']!=''){
            $field_name=$value['existing_storage_name']; 
            $field_storage = FieldStorageConfig::loadByName($type, $field_name);
            $storage_type=$field_storage->getType();
        }
        elseif(isset($value['new_storage_type'])&&$value['new_storage_type']!=''){
          $field_name=$field_prefix.$value['field_name']; 
          $storage_type=$value['new_storage_type'];
        }
        $field = FieldConfig::loadByName($type, $bundle, $field_name);
        if (empty($field)) {
          // Assign widget settings for the 'default' form mode.
           $field_storage = FieldStorageConfig::loadByName($type, $field_name);
           if(!is_object($field_storage)){
              $field_storage=FieldStorageConfig::create(array(
                'field_name' => $field_name,
                'entity_type' => $type,
                'type' => $storage_type,
                'cardinality' => 1,
              ))->save();
           }
           $field_storage = FieldStorageConfig::loadByName($type, $field_name);
           
          $field = FieldConfig::create([
            'field_storage' => $field_storage,
            'field_name' => $field_name,
            'entity_type'=>$type,
            'bundle' => $bundle,
            'label' => $value['label'],
          ]);
          $field->save();
          $type_widget = \Drupal::service('plugin.manager.field.field_type')->getDefinition($storage_type);
          
          // Assign widget settings for the 'default' form mode.
          entity_get_form_display($type,  $bundle, 'default')
            ->setComponent($field_name, [
              'type' =>$type_widget['default_widget'],
            ])
            ->save();
          // Assign display settings for the 'default' and 'teaser' view modes.
          entity_get_display($type,  $bundle, 'default')
            ->setComponent($field_name, [
              'type' => $type_widget['default_formatter'],
            ])
            ->save();
            $fcount++;
        }
      }
    }
    $counts=count($fields);

    if($fcount==1)
      $message=$fcount.t("Field is Created");
    elseif($fcount>1)
      $message=$fcount.t("Fields are Created");
    \Drupal::messenger()->addMessage($message);
    $request = \Drupal::request();
    $url=Url::fromRoute("entity.$type.field_ui_fields",[$param_key=>$bundle])->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }

 

  public function fieldNameExists($value, $element, FormStateInterface $form_state) {
    $field_name=\Drupal::config('field_ui.settings')->get('field_prefix'). $value;
    $type=$this->entityTypeId;
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($type);
    if(isset($field_storage_definitions[$field_name])){
      $fields=$form_state->getValues('fields');
      for($i=0;$i<count($fields['fields']);$i++){
        if($fields['fields'][$i]['field_name']== $value&& $fields['fields'][$i]['existing_storage_name']!=''){
          dpm($fields['fields'][$i]['existing_storage_name']);
          return false;
        }
      }
      return true;
    
    }
    else{
      return false;
    }
  }

  public function prompt(array $form, FormStateInterface $form_state) {
    return $form;
  } 

  public function addNewFields(array $form, FormStateInterface $form_state) {
    $form_state->setValue('step', $form_state->getValue('step') + 1);
    $form_state->setRebuild();
    return $form;
  }

  public function removeElement(array $form, FormStateInterface $form_state,$x) {
    $values = $form_state->getValues();
    $fields=$values['fields'];
    $counts=$values['step'];
    $ccount=0;
    for ($x = 0; $x < $counts; $x++) {
      $check=true;
      if(isset($fields[$x]['remove'])){
        if($fields[$x]['remove']==1){
          $check=false;
          unset($fields[$x]);
          $ccount++;
        }
      }
    }
    $count=$counts-$ccount;
    $fields=array_values($fields);
    $form_state->set("fields",$fields);
    $form_state->set("step",$count);
    $form_state->setRebuild();
    return $form;
  }


  public function getContainer($counts,FormStateInterface $form_state){
    $field_prefix=\Drupal::config('field_ui.settings')->get('field_prefix');
    $values = $form_state->getValues();
    // $fields=$values['fields'];
    for ($x = 0; $x < $counts; $x++) {
        $form_container[$x] = [
          '#type' => 'container',
          // '#states' => [
          //   '!visible' => [
          //     ":input[name='fields[$x][remove]']" => ['checked' => TRUE],
          //   ],
          // ],
        ];
        $form_container[$x]['label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Label'),
          '#size' => 15,
          '#attributes'=> [
             'class' => ['multifield-item'],
          ],
          '#required'=>true,
        ];
        $form_container[$x]['field_name'] = [
          '#type' => 'machine_name',
          '#field_prefix' => '<span dir="ltr">' . $field_prefix,
          '#field_suffix' => '</span>&lrm;',
          '#size' => 15,
          '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
          '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
          '#machine_name' => [
            'source' => ['fields',$x,'label'],
            'exists' => [$this, 'fieldNameExists'],
          ],
        ];
        $form_container[$x]['new_storage_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Field Type'),
          '#options' => $this->field_type_options,
          '#empty_option' => $this->t('- Select a field type -'),
          '#attributes'=> [
             'class' => ['multifield-item'],
          ],
        ];
        
        if($existing_field_storage_options = $this->getExistingFieldStorageOptions()) {
          $form_container[$x]['existing_storage_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Re-use an existing field'),
            '#options' => $existing_field_storage_options,
            '#empty_option' => $this->t('- Select an existing field -'),
          ];
        }
        else {
          $form[$x]['existing_storage_name'] = [
            '#type' => 'value',
            '#value' => FALSE,
          ];
        }
        
        $form_container[$x]['remove'] = [
          '#type' => 'checkbox',
          '#title' => $this
            ->t('Remove'),
          // '#ajax' => [
          //   'wrapper' => 'multifield-wrapper',
          //   'callback' => '::prompt',
          // ],
        ];

        $form_container[$x]['translatable'] = [
          '#type' => 'value',
          '#value' => TRUE,
        ];
      }

    return $form_container;
  }

  protected function getExistingFieldStorageOptions() {
    $options = [];
    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $types = \Drupal::entityManager()->getFieldStorageDefinitions($this->entityTypeId);
    foreach ($types as $field_name => $field_storage) {
      $field_type = $field_storage->getType();
      $field_storage=FieldStorageConfig::loadByName($this->entityTypeId,$field_name);
      if ($field_storage instanceof FieldStorageConfig
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($this->bundle, $field_storage->getBundles(), TRUE)) {
        $options[$field_name] = $this->t('@type: @field', [
          '@type' => $field_types[$field_type]['label']->__toString(),
          '@field' => $field_name,
        ])->__toString();
      }
    }
    asort($options);

    return $options;
  }

  protected function getExistingFieldLabels(array $field_names) {
    $field_ids = \Drupal::entityManager()->getStorage('field_config')->getQuery()
      ->condition('entity_type', $this->entityTypeId)
      ->condition('field_name', $field_names)
      ->execute();
    $fields = \Drupal::entityManager()->getStorage('field_config')->loadMultiple($field_ids);

    $labels = [];
    foreach ($fields as $field) {
      if (!isset($labels[$field->getName()])) {
        $labels[$field->getName()] = $field->label();
      }
    }

    $labels += array_combine($field_names, $field_names);

    return $labels;
  }

}
