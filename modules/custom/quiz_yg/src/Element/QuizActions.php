<?php

namespace Drupal\quiz_yg\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a quiz_yg actions.
 *
 * Quiz actions can have two type of actions
 * - actions - this are default actions that are always visible.
 * - dropdown_actions - actions that are in dropdown sub component.
 *
 * Usage example:
 *
 * @code
 * $form['actions'] = [
 *   '#type' => 'quiz_yg_actions',
 *   'actions' => $actions,
 *   'dropdown_actions' => $dropdown_actions,
 * ];
 * $dropdown_actions['button'] = array(
 *   '#type' => 'submit',
 * );
 * @endcode
 *
 * @FormElement("quiz_yg_actions")
 */
class QuizActions extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#pre_render' => [
        [$class, 'preRenderQuizActions'],
      ],
      '#theme' => 'quiz_yg_actions',
    ];
  }

  /**
   * Pre render callback for #type 'quiz_yg_actions'.
   *
   * @param array $element
   *   Element arrar of a #type 'quiz_yg_actions'.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderQuizActions(array $element) {
    $element['#attached']['library'][] = 'quiz_yg/drupal.quiz_yg.actions';

    if (!empty($element['dropdown_actions'])) {
      foreach (Element::children($element['dropdown_actions']) as $key) {
        $dropdown_action = &$element['dropdown_actions'][$key];
        if (isset($dropdown_action['#ajax'])) {
          $dropdown_action = RenderElement::preRenderAjaxForm($dropdown_action);
        }
        if (empty($dropdown_action['#attributes'])) {
          $dropdown_action['#attributes'] = ['class' => ['quiz_yg-dropdown-action']];
        }
        else {
          $dropdown_action['#attributes']['class'][] = 'quiz_yg-dropdown-action';
        }
      }
    }
    return $element;
  }

}
