<?php

  // $data['node__field_test_full_number']['test'] = array(
  //   'title' => t('Custom Random Field sort'),
  //   'group' => t('Content'),
  //   'help' => t('Sort Articles by Random Number.'),
  //   'sort' => array(
  //     'field' => 'field_test_full_number_value',
  //     'id' => 'RandomFieldSort',
  //   ),
  // );



/**
 * Implements hook_views_data_alter().
 */
function random_field_views_data_alter(array &$data) {

  $data['node']['node_distance_flagger'] = array(
    'title' => t('Distance flagger'),
    'field' => array(
      'title' => t('Distance flagger'),
      'help' => t('Flags a specific node type.'),
      'id' => 'distance_flagger',
    ),
  );

  $data['node_field_data']['node_distance_filter'] = array(
    'title' => t('Enhanced node type filter'),
    'filter' => array(
      'title' => t('Enhanced node type filter'),
      'help' => t('Provides a custom filter for nodes by their type.'),
      'field' => 'type',
      'id' => 'string'
    ),
  );

  $data['node_field_data']['nodes_distance'] = array(
    'title' => t('Distance titles'),
    'filter' => array(
      'title' => t('Distance titles'),
      'help' => t('Specify a list of titles a node can have.'),
      'field' => 'title',
      'id' => 'random_field_distance'
    ),
  );
}

