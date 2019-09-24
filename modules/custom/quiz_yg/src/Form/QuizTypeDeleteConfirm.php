<?php

namespace Drupal\quiz_yg\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quiz_yg\Entity\Quiz;

/**
 * Provides a form for Quiz type deletion.
 */
class QuizTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_quiz_yg = $this->entityTypeManager->getStorage('quiz_yg')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_quiz_yg) {
      $caption = '<p>' . $this->formatPlural($num_quiz_yg, '%type Quiz type is used by 1 piece of content on your site. You can not remove this %type Quiz type until you have removed all from the content.', '%type Quiz type is used by @count pieces of content on your site. You may not remove %type Quiz type until you have removed all from the content.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];

      // Optional to delete existing entities.
      $form['delete_entities'] = [
        '#type' => 'submit',
        '#submit' => [[$this, 'deleteExistingEntities']],
        '#value' => $this->formatPlural($num_quiz_yg, 'Delete existing Quiz', 'Delete all @count existing Quiz'),
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submit callback to delete quiz_yg.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deleteExistingEntities(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('quiz_yg');
    $ids = $storage->getQuery()
      ->condition('type', $this->entity->id())
      ->execute();

    if (!empty($ids)) {
      $quiz_yg = Quiz::loadMultiple($ids);

      // Delete existing entities.
      $storage->delete($quiz_yg);
      drupal_set_message($this->formatPlural(count($quiz_yg), 'Entity is successfully deleted.', 'All @count entities are successfully deleted.'));
    }

    // Set form to rebuild.
    $form_state->setRebuild();
  }

}
