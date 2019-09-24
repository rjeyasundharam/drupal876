<?php

namespace Drupal\quiz_yg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Quiz settings.
 */
class QuizSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_yg_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quiz_yg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quiz_yg.settings');
    $form['show_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show unpublished Quiz'),
      '#default_value' => $config->get('show_unpublished'),
      '#description' => $this->t('Allow users with "View unpublished quiz_yg" permission to see unpublished Quiz.')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('quiz_yg.settings');
    $config->set('show_unpublished', $form_state->getValue('show_unpublished'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
