<?php

namespace Drupal\quiz_yg\Element;

use Drupal\Core\Render\Element\Operations;
use Drupal\Core\Render\Element\RenderElement;

/**
 * {@inheritdoc}
 *
 * @RenderElement("quiz_operations")
 */
class QuizOperations extends Operations {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme' => 'links__dropbutton__operations__quiz_yg'] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderDropbutton($element) {
    $element = parent::preRenderDropbutton($element);

    // Attach #ajax events if title is a render array.
    foreach ($element['#links'] as &$link) {
      if (isset($link['title']['#ajax'])) {
        $link['title'] = RenderElement::preRenderAjaxForm($link['title']);
      }
    }

    return $element;
  }

}
