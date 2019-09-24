<?php

namespace Drupal\drupal_informations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\drupal_informations\Controller\DrupalInformationController;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Cookie;

class DrupalInformationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_information_form';
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
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Get Informations"),
      '#ajax' => [
          'callback' => '::getInformations',
          'wrapper' => 'result-fieldset', 
          'progress' => [
            'type' => 'bar',
            'message' => $this->t('Checking Theme name...'),
          ],
      ],
    ];
    $form['fields']=[
      '#type' => 'container',
      '#attributes' => ['id' => 'result-fieldset'],
    ];
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * Save away the current information.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $values=$form_state->getValues();
    $domain=$values['site_url'];
    $email=$values['email'];
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
        foreach ($themes as $theme) {
          $lpath=$path[$version].$theme."LICENSE.txt";
          $ch = curl_init($lpath);
          curl_setopt($ch, CURLOPT_NOBODY, true);
          curl_exec($ch);
          $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if($httpcode == '200') {
            $theme_list[] = $theme_detail['theme'];
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
    $options = ['absolute' => TRUE];
    $data=[];
    $data['domain']=$domain;
    if(isset($version))
      $data['version']=$version;
    if(isset($email))
      $data['email']=$email;
    if(isset($output['theme_name']))
      $data['theme_name']=$output['theme_name'];

    $url = Url::fromRoute('drupal_informations.result', $data, $options);
    $response = new RedirectResponse($url->toString());
    $response->send();
      // $dest_url = "/site-result";
      // $url = Url::fromUri('internal:' . $dest_url);
      // $form_state->setRedirectUrl( $url );
  }

  function getInformations(array &$form, FormStateInterface $form_state){
    global $base_url;
    // $values=$form_state->getValues();
    // $domain=$values['site_url'];
    // $email=$values['email'];
    $domain="Sample";
    $email='a@b.c';
    if(substr($domain, 0,4)!='http'){
      $domain='http://'.$domain;
    }
    // $ch = curl_init($domain);
    // curl_setopt($ch, CURLOPT_NOBODY, true);
    // curl_exec($ch);
    // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // if($httpcode == '504') {
    //   $data = file_get_contents("$domain");
    // }
    // else{
    //   $data = file_get_contents("$domain");  
    // }
    $version='8.x';
    if(isset($version)){
      $form['fields']['version']=[
        '#markup' => t('<p>Drupal Version : @version',['@version'=>$version]),
      ];
    }
    if(isset($email)){
      $form['fields']['user_mail']=[
        '#markup' => t('<p>User EMail : @email',['@version'=>$email]),
      ];
    }
    if(isset($output['theme_name'])){
      $form['fields']['theme_name']=[
        '#markup' => t('<p>Drupal Theme : @theme_name',['@version'=>$output['theme_name']]),
      ];
    }
    return $form['fields'];
  }
}
