<?php

namespace Drupal\cache_size_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\cache_size_block\Plugin\Block\CacheSizeBlock;

/**
 * Defines the controller for the cache sizes page.
 */
class CacheSizeController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CacheSizeController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

/**
 * Returns a renderable array for the cache sizes page.
 */
public function content() {
  $block_manager = \Drupal::service('plugin.manager.block');
  $plugin_block = $block_manager->createInstance('cache_size_block', []);

  if (!$plugin_block) {
    return ['#markup' => $this->t('Error loading cache size block.')];
  }

  return $plugin_block->build();
}
}
