<?php


namespace Drupal\drupal_informations\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "drupal_informations",
 *   admin_label = @Translation("Drupal Informations"),
 * )
 */
class DrupalInformationBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  // do something
    $formblock=[
    	'#theme' => 'drupal_theme_finder',
   		'#title' => 'Drupal Theme Finder',
      '#drupalize_form' => \Drupal::formBuilder()->buildForm("\Drupal\drupal_informations\Form\DrupalInformationForm"),
      '#drupal' => NULL,
    ];
    return $formblock;
  }


}