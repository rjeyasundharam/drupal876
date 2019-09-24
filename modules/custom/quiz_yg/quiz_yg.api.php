<?php

/**
 * @file
 * Hooks and documentation related to quiz_yg module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in
 * \Drupal\quiz_yg\Annotation\QuizBehavior.
 *
 * @param $quiz_yg_behavior
 *   The array of quiz_yg behavior plugins, keyed on the
 *   machine-readable plugin name.
 */
function hook_quiz_yg_behavior_info_alter(&$quiz_yg_behavior) {
  // Set a new label for the my_layout plugin instead of the one
  // provided in the annotation.
  $quiz_yg_behavior['my_layout']['label'] = t('New label');
}

/**
 * Alter quiz_yg widget.
 *
 * @param array $widget_actions
 *   Array with actions and dropdown widget actions.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *   - element: A form element array containing basic properties for the widget.
 *   - form_state: The current state of the form.
 *   - quiz_yg_entity: the quiz_yg entity for this widget. Might be
 *     unsaved, if we have just added a new item to the widget.
 *   - is_translating: Boolean if the widget is translating.
 *   - allow_reference_changes: Boolean if changes to structure are OK.
 */
function hook_quiz_yg_widget_actions_alter(array &$widget_actions, array &$context) {
}

/**
 * @} End of "addtogroup hooks".
 */
