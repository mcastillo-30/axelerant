<?php

namespace Drupal\axelerant_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\node\NodeInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Controller for export json.
 */
class ExportPageController extends ControllerBase {

  /**
   * @var  \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var  \ConfigFactoryInterface $configFactory
   */
  protected $configFactory;

  /**
   * ExportPageController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    EntityTypeManager $entityTypeManager, ConfigFactoryInterface  $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns a json representation for specific node page.
   * @param  string $siteapikey
   * The site api key value
   * @param string $nid
   *  The Node ID.
   * @return mixed
   *   A cacheable json response other way empty array.
   */
  public function index($siteapikey = NULL, $nid = NULL) {
    $response = new CacheableJsonResponse();
    $results = [];
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if ($node instanceof NodeInterface && $node->bundle() == 'page') {
      $results['data'] = [
        'type' => $node->bundle(),
        'id' => $node->id(),
        'attributes' => [
          'title' => $node->label(),
          'content' => $node->get('field_body')->value,
        ]
      ];
    }

    $response
      ->getCacheableMetadata()
      ->addCacheTags([
        'node_list',
      ])
      ->addCacheContexts([
        'url.query_args',
      ]);
    $response->setData($results);
    return $response;
  }

  /**
   * Helper function to validate access to custom endpoint.
   * @param  string $siteapikey
   * The site api key value.
   * @param string $nid
   *  The Node ID.
   * @return AccessResult
   *  If api key is valid allow access other way return access denied.
   */
  public function access($siteapikey = NULL, $nid = NULL) {
    $config = $this->configFactory->get('system.site');
    $system_api_key = $config->get('siteapikey');
    if ($system_api_key != $siteapikey) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
