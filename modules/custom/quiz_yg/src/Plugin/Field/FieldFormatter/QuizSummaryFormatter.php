<?php

namespace Drupal\quiz_yg\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\quiz_yg\Entity\Paragraph;
use Drupal\quiz_yg\ParagraphInterface;

/**
 * Plugin implementation of the 'paragraph_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraph_summary",
 *   label = @Translation("Paragraph summary"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class QuizSummaryFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->id()) {
        $summary = $entity->getSummary();
        $elements[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['paragraph-formatter']
          ]
        ];
        $elements[$delta]['info'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['paragraph-info']
          ]
        ];
        $elements[$delta]['info'] += $entity->getIcons();
        $elements[$delta]['summary'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['paragraph-summary']
          ]
        ];
        $elements[$delta]['summary']['description'] = [
          '#markup' => $summary,
          '#prefix' => '<div class="quiz_yg-collapsed-description">',
          '#suffix' => '</div>',
        ];
      }
    }
    $elements['#attached']['library'][] = 'quiz_yg/drupal.quiz_yg.formatter';
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $paragraph_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    if ($paragraph_type) {
      return $paragraph_type->isSubclassOf(ParagraphInterface::class);
    }

    return FALSE;
  }
}
