<?php

namespace Drupal\drupal_informations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\JsonResponse;

class DrupalInformationController extends ControllerBase {

  private $theme_list=[];
  
  protected function getModuleName() {
    return 'drupal_informations';
  }
        
  public function processStatus($tid){
    $config = \Drupal::config('drupal_informations.settings');
    return new JsonResponse([
        'message' => t('Checking theme...'),
        'percentage' => (int)$config->get($tid),
    ]);
  }
  public function display_result($data=NULL){
    $domain = \Drupal::request()->query->get('domain');   
    $version = \Drupal::request()->query->get('version');   
    $theme_name = \Drupal::request()->query->get('theme_name'); 
    $email = \Drupal::request()->query->get('email');   
  	$drupal_theme=[
      '#theme' => 'drupal_theme_finder',
   	  '#title' => 'Drupal Theme Finder',
      '#drupal' => null,
    ];
    if(isset($domain)){
      if(substr($domain, 0,4)!='http'){
        $domain='http://'.$domain;
      }
      // $drupal_data=$this->find_theme_name($domain);
      $form_state = new FormState();
      $form_state->setValue('site_url', $domain);
      if(isset($email))
        $form_state->setValue('email', $email);
      $drupal_theme['#drupalize_form'] = \Drupal::formBuilder()->buildForm("\Drupal\drupal_informations\Form\DrupalInformationForm",$form_state);
    }
    else{
      $drupal_theme['#drupalize_form'] = \Drupal::formBuilder()->getForm("\Drupal\drupal_informations\Form\DrupalInformationForm");
    }
    if (isset($theme_name)) {
      $contrib='';
      $drupal_theme['#drupal']['theme_name']=$theme_name;
      if($theme_name!='Its not drupal site' && $theme_name != 'It uses the custom theme' ){
        $themes=explode(",", $theme_name);
        foreach ($themes as $theme) {
          $theme_data=file_get_contents("https://www.drupal.org/api-d7/node.json?field_project_machine_name=$theme");
          $theme_data=json_decode($theme_data);
          if(count($theme_data->list)>0&&$contrib==''){
            $contrib=$theme;
            break;
          }
        } 
      }
    }
    if(isset($version)){
      $drupal_theme['#drupal']['version']=$version;
    }
    return $drupal_theme;
  }
  public function getPDF(){
    $html = 'this is my <b>first</b>downloadable pdf';
    $mpdf = new \Mpdf\Mpdf(['tempDir' => 'sites/default/files/tmp']); $mpdf->WriteHTML($html);
    $mpdf->Output('file.pdf', 'D');
    Exit;
  }
}
