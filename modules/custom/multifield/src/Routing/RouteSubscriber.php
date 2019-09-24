<?php

namespace Drupal\multifield\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();
        // dpm($entity_route);
        // dpm($path);
        $options = $entity_route->getOptions();
        // dpm($options);
        $bundle_entity_type = $entity_type->getBundleEntityType();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = [
            'type' => 'entity:' . $bundle_entity_type,
          ];
        }
        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        $defaults = [
          'entity_type_id' => $entity_type_id,
          'bundle_entity_type' => $bundle_entity_type
        ];
        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        $bundle="";
        // dpm($defaults);
        // dpm($entity_type);
        $params=explode("/", $path);
        $param='';
        foreach ($params as $key => $value) {
          if(strpos($value, "{")!== FALSE){
            $param=$value;
            break;
          }
        }
        // dpm($param);
        if ($param=='') {
          $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
          $param=$defaults['bundle'];
        }
        // dpm($defaults);
        $route = new Route(
          "/admin/add-fields/$entity_type_id/$param",
          [
            '_form' => '\Drupal\multifield\Form\MultifieldForm',
            '_title' => 'Add Multifield',
          ] + $defaults,
          ['_permission' => 'administer ' . $entity_type_id . ' fields'],
          $options
        );
        $route->setOption('parameters', [
          'path' => $path,
        ]);
        $collection->add("multifield.field_storage_config_add_$entity_type_id", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
