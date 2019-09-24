<?php

namespace Drupal\quiz_yg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\quiz_yg\QuizBehaviorCollection;
use Drupal\quiz_yg\QuizTypeInterface;

/**
 * Defines the QuizType entity.
 *
 * @ConfigEntityType(
 *   id = "quiz_yg_type",
 *   label = @Translation("Quiz type"),
 *   handlers = {
 *     "list_builder" = "Drupal\quiz_yg\Controller\QuizTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\quiz_yg\Form\QuizTypeForm",
 *       "edit" = "Drupal\quiz_yg\Form\QuizTypeForm",
 *       "delete" = "Drupal\quiz_yg\Form\QuizTypeDeleteConfirm"
 *     }
 *   },
 *   config_prefix = "quiz_yg_type",
 *   admin_permission = "administer quiz_yg types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "icon_uuid",
 *     "description",
 *     "behavior_plugins",
 *   },
 *   bundle_of = "quiz_yg",
 *   links = {
 *     "collection" = "/admin/structure/quiz_yg_type",
 *   }
 * )
 */

/*     "edit-form" = "/admin/structure/quiz_yg_type/{quiz_yg_type}",
 *     "delete-form" = "/admin/structure/quiz_yg_type/{quiz_yg_type}/delete",
 */
class QuizType extends ConfigEntityBundleBase implements QuizTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The QuizType ID.
   *
   * @var string
   */
  public $id;

  /**
   * The QuizType label.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this quiz_yg type.
   *
   * @var string
   */
  public $description;

  /**
   * UUID of the Quiz type icon file.
   *
   * @var string
   */
  protected $icon_uuid;

  /**
   * The Quiz type behavior plugins configuration keyed by their id.
   *
   * @var array
   */
  public $behavior_plugins = [];

  /**
   * Holds the collection of behavior plugins that are attached to this
   * Quiz type.
   *
   * @var \Drupal\quiz_yg\QuizBehaviorCollection
   */
  protected $behaviorCollection;

  /**
   * {@inheritdoc}
   */
  public function getIconFile() {
    if ($this->icon_uuid && $icon = $this->getFileByUuid($this->icon_uuid)) {
      return $icon;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugins() {
    if (!isset($this->behaviorCollection)) {
      $this->behaviorCollection = new QuizBehaviorCollection(\Drupal::service('plugin.manager.quiz_yg.behavior'), $this->behavior_plugins);
    }
    return $this->behaviorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconUrl() {
    if ($image = $this->getIconFile()) {
      return file_create_url($image->getFileUri());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugin($instance_id) {
    return $this->getBehaviorPlugins()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the file icon entity as dependency if a UUID was specified.
    if ($this->icon_uuid && $file_icon = $this->getIconFile()) {
      $this->addDependency($file_icon->getConfigDependencyKey(), $file_icon->getConfigDependencyName());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBehaviorPlugins() {
    return $this->getBehaviorPlugins()->getEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['behavior_plugins' => $this->getBehaviorPlugins()];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function hasEnabledBehaviorPlugin($plugin_id) {
    $plugins = $this->getBehaviorPlugins();
    if ($plugins->has($plugin_id)) {
      /** @var \Drupal\quiz_yg\QuizBehaviorInterface $plugin */
      $plugin = $plugins->get($plugin_id);
      $config = $plugin->getConfiguration();
      return (array_key_exists('enabled', $config) && $config['enabled'] === TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Update the file usage for the icon files.
    if (!$update || $this->icon_uuid != $this->original->icon_uuid) {
      // The icon has changed. Update file usage.
      /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
      $file_usage = \Drupal::service('file.usage');

      // Add usage of the new icon file, if it exists. It might not exist, if
      // this Quiz type was imported as configuration, or if the icon has
      // just been removed.
      if ($this->icon_uuid && $new_icon = $this->getFileByUuid($this->icon_uuid)) {
        $file_usage->add($new_icon, 'quiz_yg', 'quiz_yg_type', $this->id());
      }
      if ($update) {
        // Delete usage of the old icon file, if it exists.
        if ($this->original->icon_uuid && $old_icon = $this->getFileByUuid($this->original->icon_uuid)) {
          $file_usage->delete($old_icon, 'quiz_yg', 'quiz_yg_type', $this->id());
        }
      }
    }

    parent::postSave($storage, $update);
  }

  /**
   * Gets the file entity defined by the UUID.
   *
   * @param string $uuid
   *   The file entity's UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *  The file entity. NULL if the UUID is invalid.
   */
  protected function getFileByUuid($uuid) {
    $files = $this->entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uuid' => $uuid]);
    if ($files) {
      return current($files);
    }

    return NULL;
  }

}
