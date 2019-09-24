<?php

namespace Drupal\quiz_yg;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * Render controller for quiz_yg.
 */
class QuizViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    $build_list = parent::buildMultiple($build_list);

    // Allow enabled behavior plugin to alter the rendering.
    foreach (Element::children($build_list) as $key) {
      $build = $build_list[$key];
      $display = EntityViewDisplay::load('quiz_yg.' . $build['#quiz_yg']->bundle() . '.' . $build['#view_mode']) ?: EntityViewDisplay::load('quiz_yg.' . $build['#quiz_yg']->bundle() . '.default');
      $quiz_yg_type = $build['#quiz_yg']->getQuizType();

      // In case we use quiz_yg type with no fields the EntityViewDisplay
      // might not be available yet.
      if (!$display) {
        $display = EntityViewDisplay::create([
          'targetEntityType' => 'quiz_yg',
          'bundle' => $build['#quiz_yg']->bundle(),
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }

      foreach ($quiz_yg_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_value) {
        $plugin_value->view($build_list[$key], $build['#quiz_yg'], $display, $build['#view_mode']);
      }
      $build_list[$key]['#attached']['library'][] = 'quiz_yg/drupal.quiz_yg.unpublished';
    }

    return $build_list;
  }

}
