<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group commerce_turtlecoin
 */
class TurtlePayGatewayTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
  ];

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

    $this->store->set('default_currency', 'TRT');

    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 1000,
        'currency_code' => 'TRT',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer commerce_payment_gateway',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Tests the checkout process with TurtlePay.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testTurtlePayCheckout() {
    // Add to cart.
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('My product added to your cart.');

    // Go to cart.
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->assertSession()->pageTextContains('Shopping cart');
    $this->submitForm([], 'Checkout');

    // Checkout.
    $this->assertCheckoutProgressStep('Order information');
    $this->assertSession()->pageTextContains('Order information');
    $this->submitForm([], 'Continue to review');

    // Review.
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('TurtlePay');
    $this->submitForm([], 'Pay and complete purchase');

    // TODO: Assert order total and so on.
    $order = Order::load(1);
  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

}
