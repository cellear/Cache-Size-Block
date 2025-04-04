<?php

namespace Drupal\cache_size_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a block that displays cache sizes.
 *
 * @Block(
 *   id = "cache_size_block",
 *   admin_label = @Translation("Cache Size Block"),
 * )
 */
class CacheSizeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CacheSizeBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * Get cache bins that are managed by Drupal.
   *
   * @return array
   *   A list of cache bin table names.
   */
  protected function getCacheBins() {
    // List of known cache bins that are cleared by drush cache:rebuild
    $bins = [
      'bootstrap', 'config', 'data', 'default', 'discovery', 'dynamic_page_cache',
      'entity', 'menu', 'page', 'render', 'rest', 'tags'
    ];

    // Convert cache bin names to database table names
    return array_map(fn($bin) => 'cache_' . $bin, $bins);
  }

  /**
   * Get cache table sizes and row counts for active cache bins.
   *
   * @return array
   *   An array of cache data with bin names, sizes in MB, and row counts.
   */
  protected function getCacheData() {
    $bins = $this->getCacheBins();
    if (empty($bins)) {
      return [];
    }

    // Prepare query with only valid cache tables.
    $placeholders = implode(',', array_fill(0, count($bins), '?'));
    $query = "SELECT 
                table_name AS bin, 
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                table_rows AS row_count
              FROM information_schema.TABLES 
              WHERE table_schema = DATABASE() AND table_name IN ($placeholders)";

    $results = $this->database->query($query, array_values($bins))->fetchAll();

    $cache_data = [];
    foreach ($results as $result) {
      $cache_data[] = [
        'bin' => $result->bin,
        'size' => $result->size_mb,
        'count' => $result->row_count,
      ];
    }

    return $cache_data;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->renderCacheTable();
  }

  /**
   * Render the cache size table.
   */
  public function renderCacheTable() {
    $cache_data = $this->getCacheData();

    if (empty($cache_data)) {
      return [
        '#markup' => $this->t('No cache data found.'),
      ];
    }

    $total_size = 0;
    $total_rows = 0;

    // Sort cache data by size descending
    usort($cache_data, function($a, $b) {
      return $b['size'] <=> $a['size'];
    });

    $header = [$this->t('Cache Bin'), $this->t('Size (MB)'), $this->t('Row Count')];
    $rows = [];

    foreach ($cache_data as $data) {
      $rows[] = [$data['bin'], number_format($data['size'], 2), number_format($data['count'])];
      $total_size += $data['size'];
      $total_rows += $data['count'];
    }

    return [
      'summary' => [
        '#markup' => '<p><strong>Total Cache Size: </strong>' . number_format($total_size, 2) . ' MB<br>' .
                     '<strong>Total Rows: </strong>' . number_format($total_rows) . '</p>',
        '#allowed_tags' => ['p', 'strong', 'br'],
      ],
      'table' => [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
    ];
  }
}
