<?php

namespace Drupal\drupal_informations;
use Symfony\Component\HttpFoundation\RedirectResponse;
													
class FindThemes{

  public static function themeListsFinishedCallback($success, $results, $operations){
  	global $base_url;
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One Theme in your site.', '@count themes are Find in your website.'
      );     
    }
    else {
      $message = t('Finished with an error.');
    }
    if(count($results)>0){ 
      $contrib='';
      setcookie('theme_list',implode(",", $results));
      foreach ($results as $theme) {
        $theme_data=file_get_contents("https://www.drupal.org/api-d7/node.json?field_project_machine_name=$theme");
        $theme_data=json_decode($theme_data);
        if(count($theme_data->list)>0&&$contrib==''){
          $contrib=$theme;
          break;
        }
      } 
    }
    else{
      setcookie('theme_list','It uses the custom theme');
    }
  
    if(isset($_COOKIE['email'])){
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'drupal_informations';
      $key = 'send_site_status';
      $to = $_COOKIE['email'];
      $params['subject'] = $_COOKIE['domain'].' Informations';
      $params['message'] = '<h1>'.$_COOKIE['domain'].' Site Theme Detail</h1>';
      $params['message'].= '<p>Theme Name :'.$_COOKIE['theme_list'].'</p>';
      if(isset($_COOKIE['version'])){
        $params['message'].= '<p>Drupal version :'.$_COOKIE['version'].'</p>';
      }
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
        \Drupal::messenger()->addMessage(t('This site detail has been sent to your mail address.'));
      }
      user_cookie_delete('email');
    }

    // \Drupal::messenger()->addMessage($message);  
    $url=$base_url."/site-result";
    $response = new RedirectResponse($url);
    $response->send();
    
  }

  public static function check_themefound($theme_detail,$theme_list, &$context){
    $message = 'Check '.$theme_detail['theme'].' Theme is found...';
    $path=$theme_detail['path'].$theme_detail['theme']."/LICENSE.txt";
    $ch = curl_init($path);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpcode == '200') {
      $context['results'][] = $theme_detail['theme'];
    }
    curl_close($ch);
    $context['message'] = $message;
  }
}
