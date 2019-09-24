<?php

namespace Drupal\quiz_yg\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\Messenger;
use Drupal\field_ui\FieldUI;
use Drupal\quiz_yg\QuizBehaviorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for quiz_yg type forms.
 */
class QuizTypeForm extends EntityForm {

  /**
   * The quiz_yg behavior plugin manager service.
   *
   * @var \Drupal\quiz_yg\QuizBehaviorManager
   */
  protected $quiz_ygBehaviorManager;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\quiz_yg\QuizTypeInterface
   */
  protected $entity;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * GeneralSettingsForm constructor.
   *
   * @param \Drupal\quiz_yg\QuizBehaviorManager $quiz_yg_behavior_manager
   *   The quiz_yg type feature manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(QuizBehaviorManager $quiz_yg_behavior_manager, Messenger $messenger) {
    $this->quiz_ygBehaviorManager = $quiz_yg_behavior_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.quiz_yg.behavior'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $quiz_yg_type = $this->entity;

    if (!$quiz_yg_type->isNew()) {
      $form['#title'] = (t('Edit %title quiz_yg type', [
        '%title' => $quiz_yg_type->label(),
      ]));
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $quiz_yg_type->label(),
      '#description' => $this->t("Label for the Quiz type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $quiz_yg_type->id(),
      '#machine_name' => array(
        'exists' => 'quiz_yg_type_load',
      ),
      '#maxlength' => 32,
      '#disabled' => !$quiz_yg_type->isNew(),
    );

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $quiz_yg_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new quiz_yg</em> page.'),
    ];

    // Loop over the plugins that can be applied to this quiz_yg type.
    if ($behavior_plugin_definitions = $this->quiz_ygBehaviorManager->getApplicableDefinitions($quiz_yg_type)) {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('Behavior plugins are only supported by the EXPERIMENTAL quiz_yg widget.'),
        '#attributes' => ['class' => ['messages', 'messages--warning']]
      ];
      $form['behavior_plugins'] = [
        '#type' => 'details',
        '#title' => $this->t('Behaviors'),
        '#tree' => TRUE,
        '#open' => TRUE
      ];
      $config = $quiz_yg_type->get('behavior_plugins');
      // Alphabetically sort plugins by plugin label.
      uasort($behavior_plugin_definitions, function ($a, $b) {
        return strcmp($a['label'], $b['label']);
      });
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        $description = $behavior_plugin_definition['description'];
        $form['behavior_plugins'][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $behavior_plugin_definition['label'],
          '#title_display' => 'after',
          '#description' => $description,
          '#default_value' => !empty($config[$id]['enabled']),
        ];
        $form['behavior_plugins'][$id]['settings'] = [];
        $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
        $behavior_plugin = $quiz_yg_type->getBehaviorPlugin($id);
        if ($settings = $behavior_plugin->buildConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state)) {
          $form['behavior_plugins'][$id]['settings'] = $settings + [
            '#type' => 'fieldset',
            '#title' => $behavior_plugin_definition['label'],
            '#states' => [
              'visible' => [
                  ':input[name="behavior_plugins[' . $id . '][enabled]"]' => ['checked' => TRUE],
              ]
            ]
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $quiz_yg_type = $this->entity;

    if ($behavior_plugin_definitions = $this->quiz_ygBehaviorManager->getApplicableDefinitions($quiz_yg_type)) {
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        // Only validate if the plugin is enabled and has settings.
        if (isset($form['behavior_plugins'][$id]['settings']) && $form_state->getValue(['behavior_plugins', $id, 'enabled'])) {
          $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
          $behavior_plugin = $quiz_yg_type->getBehaviorPlugin($id);
          $behavior_plugin->validateConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $quiz_yg_type = $this->entity;

    if ($behavior_plugin_definitions = $this->quiz_ygBehaviorManager->getApplicableDefinitions($quiz_yg_type)) {
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        $behavior_plugin = $quiz_yg_type->getBehaviorPlugin($id);

        // If the behavior is enabled, initialize the configuration with the
        // enabled key and then let it process the form input.
        if ($form_state->getValue(['behavior_plugins', $id, 'enabled'])) {
          $behavior_plugin->setConfiguration(['enabled' => TRUE]);
          if (isset($form['behavior_plugins'][$id]['settings'])) {
            $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
            $behavior_plugin->submitConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state);
          }
        }
        else {
          // The plugin is not enabled, reset to default configuration.
          $behavior_plugin->setConfiguration([]);
        }
      }
    }

    $status = $quiz_yg_type->save();
    $this->messenger->addMessage($this->t('Saved the %label Quiz type.', array(
      '%label' => $quiz_yg_type->label(),
    )));
    if (($status == SAVED_NEW && \Drupal::moduleHandler()->moduleExists('field_ui'))
      && $route_info = FieldUI::getOverviewRouteInfo('quiz_yg', $quiz_yg_type->id())) {
      $form_state->setRedirectUrl($route_info);
    }
    else {
      $form_state->setRedirect('entity.quiz_yg_type.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $form = parent::actions($form, $form_state);

    // We want to display the button only on add page.
    if ($this->entity->isNew() && \Drupal::moduleHandler()->moduleExists('field_ui')) {
      $form['submit']['#value'] = $this->t('Save and manage fields');
    }

    return $form;
  }

}
