<?php

namespace Drupal\quiz_yg\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a QuizBehavior annotation object.
 *
 * Quiz behavior builders handle extra settings for the quiz
 * entity.
 *
 * @Annotation
 *
 */
class QuizBehavior extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the quiz_yg behavior plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The plugin description.
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $description;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
