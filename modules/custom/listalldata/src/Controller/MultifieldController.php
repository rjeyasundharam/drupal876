<?php

namespace Drupal\multifield\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
/**
 * Controller routines for AJAX example routes.
 */
class MultifieldController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'multifield';
  }
}
