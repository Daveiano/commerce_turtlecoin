<?php

namespace Drupal\Tests\commerce_turtlecoin\Traits;

use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce\Traits\CommerceBrowserTestTrait;
use Drupal\Tests\RandomGeneratorTrait;

trait CommerceTrutlecoinOrderDataTrait {

  use CommerceBrowserTestTrait;
  use RandomGeneratorTrait;

  protected function createVariation() {
    return $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 1,
        'currency_code' => 'USD',
      ],
    ]);
  }

  /**
   * Create a dummy commerce product.
   *
   * @param \Drupal\commerce_store\Entity\Store $store
   *   The store.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created product.
   */
  protected function createProduct(Store $store) {
    return $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$this->createVariation()],
      'stores' => [$store],
    ]);
  }

}
