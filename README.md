# Cache-Size-Block
This is a module that checks the size of a Drupal Cache, created entirely by ChatGPT in 4 prompts

Here is a [link to the ChatGPT session](https://chatgpt.com/share/67f022d5-ea0c-8007-88ff-34e835849f28) 

how could i write a drupal module that would display cache sizes in a block?

To write a Drupal module that displays cache sizes in a block, follow these steps:

## 1. Create a Custom Module Directory and Files
Create a module inside the modules/custom directory (or modules/contrib if you plan to distribute it). We'll call it cache_size_block.

```
mkdir -p web/modules/custom/cache_size_block/src/Plugin/Block
touch web/modules/custom/cache_size_block/cache_size_block.info.yml
touch web/modules/custom/cache_size_block/cache_size_block.module
touch web/modules/custom/cache_size_block/src/Plugin/Block/CacheSizeBlock.php
```

## 2. Define the Module in cache_size_block.info.yml
This file tells Drupal about your module.

```
name: 'Cache Size Block'
type: module
description: 'Provides a block that displays the sizes of cache bins.'
core_version_requirement: ^9 || ^10
package: 'Custom'
dependencies:
  - drupal:block
```

## 3. Implement the Block in src/Plugin/Block/CacheSizeBlock.php
This block will retrieve cache sizes and display them.

```php
<?php

namespace Drupal\cache_size_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

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
   * {@inheritdoc}
   */
  public function build() {
    $cache_sizes = $this->getCacheSizes();
    
    if (empty($cache_sizes)) {
      return [
        '#markup' => $this->t('No cache data found.'),
      ];
    }

    $header = [$this->t('Cache Bin'), $this->t('Size (MB)')];
    $rows = [];
    
    foreach ($cache_sizes as $bin => $size) {
      $rows[] = [$bin, number_format($size, 2)];
    }

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Get cache sizes from database tables.
   *
   * @return array
   *   An associative array where keys are cache bin names and values are sizes in MB.
   */
  protected function getCacheSizes() {
    $query = "SELECT table_name AS bin, round((data_length + index_length) / 1024 / 1024, 2) AS size_mb 
              FROM information_schema.TABLES 
              WHERE table_schema = DATABASE() AND table_name LIKE 'cache_%'";

    $results = $this->database->query($query)->fetchAllAssoc('bin');

    $sizes = [];
    foreach ($results as $bin => $result) {
      $sizes[$bin] = $result->size_mb;
    }

    return $sizes;
  }

}
```

## 4. Enable the Module and Add the Block
Enable the module:
```
drush en cache_size_block -y
```
Or via UI: Go to Extend → Search for Cache Size Block → Enable.

## 5. Place the block:

Go to Structure → Block layout.

Find a suitable region.

Click Place block, search for Cache Size Block, and add it.

##  6. How It Works

- The block queries the database for all cache_* tables.
- It calculates their size in MB using information_schema.TABLES.
- The sizes are displayed in a table format.

## Possible Enhancements
- Add a configuration form to filter which cache bins to display.
- Show cache counts alongside sizes.
- Provide a link to clear specific cache bins.
- Would you like any improvements or additional features? 🚀

You said:
Can you generate a downloadable zip file with these files as listed?

ChatGPT said:
I have generated the module as a zip file. You can download it here:

Download cache_size_block.zip

Let me know if you need any modifications! 🚀 ​​

You said:
Thank you, that worked perfectly! 
