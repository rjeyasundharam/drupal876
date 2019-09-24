<?php

namespace Drupal\random_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
/**
 * Controller routines for AJAX example routes.
 */
class RandomfieldController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public $values=['971','771','414','531','612','642','613','164','265','171'];
  
  protected function getModuleName() {
    return 'multifield';
  }
 
  public function ViewsDataAlter(array &$data){
  	// dpm(array_keys($data));
  }
  
  public function getValues(){
  	return $values;
  }

  public function SetTestValues(){
	
  }
}
