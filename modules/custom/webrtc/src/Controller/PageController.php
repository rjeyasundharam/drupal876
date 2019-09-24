<?php

namespace Drupal\webrtc\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  /**
   * View.
   *
   * @return string
   *   Return Hello string.
   */
  public function view() {
    $build = [];
    $build['webrtc_page'] = [
      '#theme' => 'webrtc',
      '#children' => [],
    ];
    $build['webrtc_page']['#cache']['max-age'] = 0;
    return $build;
  }

}
