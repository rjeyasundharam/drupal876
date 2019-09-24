<?php

/**
 * @file
 * Contains Drupal\xray\Controller\xrayController.
 */

namespace Drupal\xray\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class xrayController.
 *
 * @package Drupal\xray\Controller
 */
class xrayController extends ControllerBase {
  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function modules() {
    $site_url = isset($_POST['siteurl']) ? $_POST['siteurl'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $count = isset($_POST['count']) ? $_POST['count'] : '';
    $flag = 0;
    $output = '';
    if ($cache = \Drupal::cache()->get('xray_top_modules')) {
      $top_modules = $cache->data;
    }
    else {
      $str = file_get_contents('https://www.drupal.org/api-d7/node.json?type=project_module&field_project_type=full&limit=10&sort=field_download_count&direction=DESC');
      $json = json_decode($str, true);
      foreach ($json['list'] as $value) {
        $top_modules[] = $value['field_project_machine_name'];
      }
      \Drupal::cache()->set('xray_top_modules', $top_modules);
    }
    if(!isset($top_modules[$count])) {
      $flag = 1;
      $node = entity_create('node', array(
          'type' => 'xray',
          'nid' => NULL,
          'title' => $site_url,
          'field_email' => $email,
          'field_modules_list' => array_values($_SESSION['xray_module']),
        )
      );
      $node->save();
      unset($_SESSION['xray_module']);
    }
    else {
      $ch = curl_init($site_url . "/sites/all/modules/" . $top_modules[$count] ."/LICENSE.txt");
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_exec($ch);
      $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $d8_available = "";
      if($retcode == '200') {
        $up_url = "http://updates.drupal.org/release-history/". $top_modules[$count] . "/8.x";
        $update_info = simplexml_load_file($up_url);
        if(isset($update_info->api_version)) {
          $d8_available = " - Drupal 8 branch is available";
        }
        $output = '<li><a href="http://www.drupal.org/project/' . $top_modules[$count] . '">' . $top_modules[$count] . '</a>' . $d8_available .'</li>';
        $_SESSION['xray_module'][$top_modules[$count]] = $top_modules[$count];
      }
      curl_close($ch);
    }
    return new JsonResponse(array('data' => $output, 'count' => ++$count, 'flag' => $flag, 'email' => $email));
  }

  /**
   * Version Callback
   * @return JSON Version
   */
  public function version() {
    $site_url = $_POST['siteurl'];
    $file = file_get_contents('http://' . $site_url . '/CHANGELOG.txt');
    $line = explode(',', $file);
    $output = '<p>This site runs on <b>' . $line[0] . '</b></p>';
    return new JsonResponse(array('data' => $output));
  }
}
