<?php

namespace Drupal\drupal_informations\Form;

use Drupal\Core\Form\{FormBase,FormStateInterface};
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\{RedirectResponse,JsonResponse};

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class DrupalInformationForm extends FormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multifield_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your site Url: '),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('site_url')
    ];
    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Your Email'),
      '#description' => $this->t('Send Information to your email address'),
      '#default_value' => $form_state->getValue('email')
    );
    $form['result'] = $this->getResults($form_state);
    $form['actions'] = ['#type' => 'actions'];
    $config = \Drupal::service('config.factory')->getEditable('drupal_informations.settings');
    $id=$config->get('theme_pid');
    $theme_pid='theme_'.$id;
    $config->set('theme_pid',($id+1))->save();
    $form['tid']=[
      '#type'=>'hidden',
      '#value'=>$theme_pid,
    ];
    $form['actions']['get_info'] = [
      '#type' => 'submit',
      '#value' => $this->t("Get Informations"),
      '#ajax' => [
        'wrapper' => 'drupal-theme-wrapper',
        'callback' => '::prompt',
        'progress' => [
          'type' => 'bar',
          'message' => $this->t('Checking Theme name...'),
          'url'=>$base_url.'/site-result/import/progress/'.$theme_pid,
          'interval' => '2000'
        ],
      ],
    ];
    $form['#prefix'] = '<div id="drupal-theme-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  public function prompt(array $form, FormStateInterface $form_state) {
    // dpm($form_state);
    return $form;
  } 

  public function getResults(FormStateInterface $form_state){
    global $base_url;
    $values=$form_state->getUserInput();
    $domain=$values['site_url'];
    $email=$values['email'];
    $email=$values['email'];
    $tid=$values['tid'];
    $config = \Drupal::service('config.factory')->getEditable('drupal_informations.settings');
        
    if($domain==''){
      $form_container['fields']['theme_name']=[
        '#markup' => t('Site Url Expected'),
      ];
      return $form_container;
    }
    if(substr($domain, 0,4)!='http'){
      $domain='http://'.$domain;
    }
    $ch = curl_init($domain);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpcode == '504') {
      $data = file_get_contents("$domain");
    }
    else{
      $data = file_get_contents("$domain");  
    }
    preg_match('/name=\"Generator\" content=\"([^<]+)\"/i', $data, $drupdata);
    if(strpos($drupdata[1], 'drupal') !== false){
      if(strpos($drupdata[1], '7') !== false ){
        $version='7.x';
        $output['version']=$version;
        preg_match_all('/\/sites\/all\/themes\/(?P<theme_names>\w+)\//', $data, $matches);
        preg_match_all('/\/sites\/all\/themes\/contrib\/(?P<theme_names>\w+)\//', $data, $comatches);
        preg_match_all('/\/sites\/all\/themes\/custom\/(?P<theme_names>\w+)\//', $data, $cumatches);
        preg_match_all('/jQuery\.extend\(Drupal\.settings\, ([^<]+)\)/', $data, $jmatches);
      }
      elseif(strpos($drupdata[1], '8') !== false){
        $version='8.x';
        $output['version']=$version;
        user_cookie_save(['version'=>$version]);
        preg_match_all('/\/themes\/contrib\/(?P<theme_names>\w+)\//', $data, $comatches);
        preg_match_all('/\/themes\/custom\/(?P<theme_names>\w+)\//', $data, $cumatches);
        preg_match_all('/\"\/themes\/(?P<theme_names>\w+)\//', $data, $matches);
        preg_match('/<script type=\"application\/json\" data-drupal-selector=\"drupal-settings-json\">([^<]+)<\/script>/i', $data, $jsmatches);
      }
      elseif(strpos($drupdata[1], '6') !== false ){
        $version='6.x';
        $output['version']=$version;
        preg_match_all('/\/sites\/all\/themes\/(?P<theme_names>\w+)\//', $data, $matches);
        preg_match_all('/\/sites\/all\/themes\/contrib\/(?P<theme_names>\w+)\//', $data, $comatches);
        preg_match_all('/jQuery\.extend\(Drupal\.settings\, ([^<]+)\)/', $data, $jmatches);
      }
      
      if(count($cumatches["theme_names"])>0){
        $found=1;
        $values = array_count_values($cumatches["theme_names"]);
        arsort($values);
        $output['theme_name']=key($values); 
      }
      elseif(count($comatches["theme_names"])>0){
        $found=1;
        $values = array_count_values($comatches["theme_names"]);
        arsort($values);
        $output['theme_name']=key($values); 
      }
      elseif(count($matches["theme_names"])>0){
        $found=1;
        $values = array_count_values($matches["theme_names"]);
        arsort($values);
        $output['theme_name']=key($values); 
      }
      elseif(isset($jmatches[1][0])){
        $values=json_decode($jmatches[1][0]);
        if(isset($values->ajaxPageState->theme)){
          $found=1;
          $output['theme_name']=$values->ajaxPageState->theme;
        }
      }
      elseif(isset($jsmatches[1])){
        $values = json_decode($jsmatches[1]);
        if(isset($values->ajaxPageState->theme)){
          $found=1;
          $output['theme_name']=$values->ajaxPageState->theme;
        }
      }
      
      if($found==0){
        $connection = \Drupal::database();
        $query = $connection->select('theme_information', 'ti');
        $query->condition('theme_version', $version);
        $query->addField('ti', 'theme_name');
        $theme_list=[];
        $path['6.x']=$domain."/sites/all/themes/";
        $path['7.x']=$domain."/sites/all/themes/";
        $path['8.x']=$domain."/themes/";
        $themes=$query->execute()->fetchcol();
        $noth=count($themes);
        foreach ($themes as $key=>$theme) {
          $lpath=$path[$version].$theme."/LICENSE.txt";
          $ch = curl_init($lpath);
          $config->set($tid, ($key*100)/$noth)->save();
          curl_setopt($ch, CURLOPT_NOBODY, true);
          curl_exec($ch);
          $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if($httpcode == '200') {
            $theme_list[] = $theme;
          }
          curl_close($ch);
        }
        $contrib='';
        if(count($theme_list)>0){ 
          foreach ($theme_list as $theme) {
            $theme_data=file_get_contents("https://www.drupal.org/api-d7/node.json?field_project_machine_name=$theme");
            $theme_data=json_decode($theme_data);
            if(count($theme_data->list)>0&&$contrib==''){
              $contrib=$theme;
              break;
            }
          } 
        }
        $output['theme_name']=implode(",", $theme_list);
        if(isset($email)){
          $mailManager = \Drupal::service('plugin.manager.mail');
          $module = 'drupal_informations';
          $key = 'send_site_status';
          $to = $email;
          $params['subject'] = $domain.' Informations';
          $params['message'] = '<h1>'.$domain.' Site Theme Detail</h1>';
          if(count($theme_list)>0){
            $params['message'].= '<p>Theme Name :'.implode(",", $theme_list).'</p>';            
            $params['message'].= '<p>Contributed Theme Found in this site</p>';
          }
          else{
            $params['message'].= "<p>It uses the custom theme.</p>";
          }
          if(isset($version)){
            $params['message'].= '<p>Drupal version :'.$version.'</p>';
          }
          $langcode = 'en';
          $send = true;
          $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
          if ($result['result'] !== true) {
            \Drupal::messenger()->addError(t('There was a problem sending your message and it was not sent.'));
          }
          else {
            \Drupal::messenger()->addMessage(t('This site detail has been sent to your mail address.'));
          }
        }
      }
      else{   
        $config->set($tid, 100)->save();
           
        if(isset($email)){
          $mailManager = \Drupal::service('plugin.manager.mail');
          $module = 'drupal_informations';
          $key = 'send_site_status';
          $to = $email;
          $contrib=0;
          $theme_data=file_get_contents("https://www.drupal.org/api-d7/node.json?field_project_machine_name=".$output['theme_name']);
          $theme_data=json_decode($theme_data);
          if(count($theme_data->list)>0&&$contrib==''){
            $contrib=$theme;
          }
          $params['subject'] = "$domain Informations";
          $params['message'] = '<h1>'.$domain.' Site Theme Detail</h1>';
          $params['message'].= '<p>Theme Name :'.$output['theme_name'].'</p>';
          $params['message'].= '<p>Drupal version :'.$output['version'].'</p>';
          if($contrib!=''){
            $params['message'].= '<p>Contributed Theme Installed in this site</p>';
          }
          $langcode = 'en';
          $send = true;
          $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
          if ($result['result'] !== true) {
            \Drupal::messenger()->addError(t('There was a problem sending your message and it was not sent.'));
          }
          else {
            \Drupal::messenger()->addMessage(t('This site detail has been sent to your mail address.'));
          }
        }
      }
    }
    else{
      if(isset($email)){
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'drupal_informations';
        $key = 'send_site_status';
        $to = $email;
        $contrib=0;
        $theme_data=file_get_contents("https://www.drupal.org/api-d7/node.json?field_project_machine_name=".$output['theme_name']);
        $theme_data=json_decode($theme_data);
        if(count($theme_data->list)>0&&$contrib==''){
          $contrib=$theme;
        }
        $params['subject'] = "$domain Informations";
        $params['message'] = '<h1>'.$domain.' Site Theme Detail</h1>';
        $params['message'].= '<p>Theme Name :'.$output['theme_name'].'</p>';
        $params['message'].= '<p>Drupal version :'.$output['version'].'</p>';
        if($contrib!=''){
          $params['message'].= '<p>Contributed Theme Found in this site</p>';
        }
        $langcode = 'en';
        $send = true;
        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
        if ($result['result'] !== true) {
          \Drupal::messenger()->addError(t('There was a problem sending your message and it was not sent.'));
        }
        else {
          \Drupal::messenger()->addMessage(t('Your message has been sent.'));
        }
      }
    }
    if(isset($version)){
      $form_container['fields']['version']=[
        '#markup' => t('<p>Drupal Version : @version',['@version'=>$version]),
      ];
    }
    if(isset($email)){
      $form_container['fields']['user_mail']=[
        '#markup' => t('<p>User EMail : @email',['@email'=>$email]),
      ];
    }
    if(isset($output['theme_name'])){
      $form_container['fields']['theme_name']=[
        '#markup' => t('<p>Drupal Theme : @theme_name',['@theme_name'=>$output['theme_name']]),
      ];
    }
    $config->set($tid, 0)->save();
    return $form_container;
    Drupal::configFactory()->getEditable('drupal_informations.settings')->clear($tid)->save();
  }
}

