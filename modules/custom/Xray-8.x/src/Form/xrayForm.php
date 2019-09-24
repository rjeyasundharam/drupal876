<?php
/**
 * @file
 * Contains \Drupal\xray\Form\xrayForm.
 */

namespace Drupal\xray\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * xray form.
 */
class xrayForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'xray/xray.form';
    $form['site'] = array(
      '#type' => 'textfield',
      '#title' => t('Site Name'),
    );
    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email Address'),
    );
    $form['preview'] = array(
      '#type' => 'button',
      '#value' => t('scan'),
      '#attributes' => array('class' => array('xray-preview')),
    );
    $form['version'] = array(
      '#markup' => '<div class="version"></div>',
    );
    $form['list'] = array(
      '#markup' => '<ul class="module-list"></ul><div class="loading"></div>',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
?>
