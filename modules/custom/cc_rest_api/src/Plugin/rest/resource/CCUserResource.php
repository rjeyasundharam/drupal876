<?php

namespace Drupal\cc_rest_api\Plugin\rest\resource;


use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Component\Serialization\Json;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Provides Cannabinoid ClinicalResource
 *
 * @RestResource(
 *   id = "ccuser_resource",
 *   label = @Translation(" Create User"),
 *   uri_paths = {
 *     "canonical" = "/cc_user",
       "https://www.drupal.org/link-relations/create" = "/cc_user"
 *   }
 * )
 */
class CCUserResource extends ResourceBase {
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $query = \Drupal::request()->query;
    $response = ['none'=>1];
    return new ResourceResponse($response);
  }

  public function post(Request $request) {
    $query = \Drupal::request()->query;
    $response = [];
    $params = Json::decode($request->getContent());
    extract($params);  
    $response["FindType"] = "Vehicle Types";
    $response['TypeList']=$this->getTypes();
    $response["ServerMsg"]=[ 
      "Msg" => "SUCCESS",
      "DisplayMsg" => "List Of Vehicle types"
    ];
    return new ResourceResponse($response);
  }
