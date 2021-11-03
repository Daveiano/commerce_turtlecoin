<?php

namespace Drupal\Tests\commerce_turtlecoin\Kernel;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Test TRTL currency config.
 */
class TurtlecoinCurrencyTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_turtlecoin',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['commerce_turtlecoin']);
  }

  /**
   * Tests the imported currency.
   */
  public function testCurrencyImport() {
    $currency = Currency::load('TRT');
    $this->assertNotEmpty($currency);
    $this->assertEquals('TRT', $currency->getCurrencyCode());
    $this->assertEquals('TurtleCoin', $currency->getName());
    $this->assertEquals('000', $currency->getNumericCode());
    $this->assertEquals('TRTL', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
  }

}
