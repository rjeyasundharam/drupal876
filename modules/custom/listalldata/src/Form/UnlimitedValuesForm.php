<?php

namespace Drupal\multifield\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UnlimitedValuesForm.
 *
 * @package Drupal\demo_form_unlimited_values\Form
 */
class UnlimitedValuesForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unlimited_values_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // State that the form needs to allow for a hierarchy (ie, multiple
    // names with our names key).
    $form['#tree'] = TRUE;

    // Initial number of names.
    if (!$form_state->get('num_names')) {
      $form_state->set('num_names', 1);
    }

    // Container for our repeating fields.
    $form['names'] = [
      '#type' => 'container',
    ];

    // Add our names fields.
    for ($x = 0; $x < $form_state->get('num_names'); $x++) {
      $form['names'][$x]['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First name @num', ['@num' => ($x + 1)]),
      ];

      $form['names'][$x]['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last name @num', ['@num' => ($x + 1)]),
      ];
    }

    // Button to add more names.
    $form['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another name'),
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Decide what action to take based on which button the user clicked.
    switch ($values['op']) {
      case 'Add another name':
        $this->addNewFields($form, $form_state);
        break;

      default:
        $this->finalSubmit($form, $form_state);
    }
  }

  /**
   * Handle adding new.
   */
  private function addNewFields(array &$form, FormStateInterface $form_state) {

    // Add 1 to the number of names.
    $num_names = $form_state->get('num_names');
    $form_state->set('num_names', ($num_names + 1));

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Handle submit.
   */
  private function finalSubmit(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Do what you want with the final data here'), 'status');
  }

}