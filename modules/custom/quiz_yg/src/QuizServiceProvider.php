<?php

namespace Drupal\quiz_yg;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Quiz.
 */
class QuizServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // Check for installed Replicate module.
    if (isset($modules['replicate']) ) {
      // Add a Replicate field event subscriber.
      $service_definition = new Definition(
        'Drupal\quiz_yg\EventSubscriber\ReplicateFieldSubscriber',
        [new Reference('replicate.replicator')]
      );
      $service_definition->addTag('event_subscriber');
      $service_definition->setPublic(TRUE);
      $container->setDefinition('replicate.event_subscriber.quiz_yg', $service_definition);
    }
  }
}
