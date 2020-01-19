<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\commerce_price\Entity\Currency;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group commerce_turtlecoin
 */
class TurtleCurrency extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['commerce_turtlecoin'];

  /**
   * The theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['administer site configuration']);

    $this->drupalLogin($this->user);
  }

  /**
   * Tests the initial currency creation.
   */
  public function testInitialCurrency() {
    // We are expecting to import 'TRT'.
    $currency = Currency::load('TRT');
    $this->assertNotEmpty($currency);
  }

  /**
   * Tests the imported currency.
   */
  public function testCurrencyImport() {
    $currency = Currency::load('TRT');
    $this->assertEquals('TRT', $currency->getCurrencyCode());
    $this->assertEquals('TurtleCoin', $currency->getName());
    $this->assertEquals('000', $currency->getNumericCode());
    $this->assertEquals('TRTL', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
  }

}
