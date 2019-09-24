<?php

namespace Drupal\quiz_yg\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Feeds target plugin for Quiz fields.
 *
 * @FeedsTarget(
 *   id = "quiz_yg",
 *   field_types = {"entity_reference_revisions"},
 *   arguments = {"@entity.manager", "@current_user"}
 * )
 */
class Quiz extends Text implements ConfigurableTargetInterface {

  /**
   * The quiz_yg storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $quiz_ygStorage;

  /**
   * The quiz_yg type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $quiz_ygTypeStorage;

  /**
   * The field config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * Constructs the target plugin.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
    $this->quiz_ygStorage = $entity_type_manager->getStorage('quiz_yg');
    $this->quiz_ygTypeStorage = $entity_type_manager->getStorage('quiz_yg_type');
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'quiz_yg_type' => NULL,
      'quiz_yg_field' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['quiz_yg_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Quiz type'),
      '#required' => TRUE,
      '#options' => array_map(function(EntityInterface $quiz_yg_type) {
        return $quiz_yg_type->label();
      }, $this->quiz_ygTypeStorage->loadMultiple()),
      '#default_value' => $this->configuration['quiz_yg_type'],
    ];

    // Load and filter field configs to create options.
    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->fieldConfigStorage->loadByProperties([
      'entity_type' => 'quiz_yg',
      'bundle' => $this->configuration['quiz_yg_type'],
    ]);
    $field_options = [];
    foreach ($field_configs as $field_config) {
      if (in_array($field_config->getType(), ['text', 'text_long', 'text_with_summary'])) {
        $field_options[$field_config->getName()] = $field_config->label();
      }
    }

    $form['quiz_yg_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraph field'),
      '#description' => $this->t('<strong>Note:</strong> Field options do not appear until a type has been chosen and saved.'),
      '#options' => $field_options,
    ];

    $form = parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = $this->t('Not yet configured.');
    $quiz_yg_type_id = $this->configuration['quiz_yg_type'];
    $quiz_yg_field_name = $this->configuration['quiz_yg_field'];
    if ($quiz_yg_type_id && $quiz_yg_type = $this->quiz_ygTypeStorage->load($quiz_yg_type_id)) {
      if ($quiz_yg_field_name && $quiz_yg_field = $this->fieldConfigStorage->load('quiz_yg.' . $quiz_yg_type_id . '.' . $quiz_yg_field_name)) {
        $summary = $this->t('Using the %field field on a %type quiz_yg.', [
          '%field' => $quiz_yg_field->label(),
          '%type' => $quiz_yg_type->label(),
        ]);
      }
    }
    return $summary . '<br>' . parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    parent::prepareValue($delta, $values);
    $quiz_yg = $this->quiz_ygStorage->create([
      'type' => $this->configuration['quiz_yg_type'],
      $this->configuration['quiz_yg_field'] => $values,
    ]);
    $values = ['entity' => $quiz_yg];
  }

}
