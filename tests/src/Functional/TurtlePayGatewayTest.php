<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_turtlecoin\TurtleCoinService;

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

    // Assert payment instructions.
    $this->assertSession()->pageTextContains('Please transfer the amount of 1000 TRTL to TRTL');
    $this->assertSession()->pageTextContains('Warning: This address will only be active for about 60 Blocks (1h)');

    // Assert some order values.
    $order = Order::load(1);
    $this->assertEquals('completed', $order->getState()->getId());
    $this->assertEquals('complete', $order->get('checkout_step')->first()->getValue()['value']);
    $this->assertEquals('turtlepay_payment_gateway', $order->get('payment_gateway')->entity->getPluginId());
    $this->assertEquals(new Price(1000, 'TRT'), $order->getTotalPrice());

    // Validate the created payment.
    $payment = Payment::load(1);
    $this->assertEquals(new Price(1000, 'TRT'), $payment->getAmount());
    $turtlepay_response = $payment->get('turtlepay_checkout_response')->value;
    $turtlepay_response = Json::decode($turtlepay_response);
    $this->assertNotEmpty($turtlepay_response, 'No response from TurtlePay.');
    $this->assertEquals('pending', $payment->getState()->getId());

    // Validate the address.
    $this->assertTrue(TurtleCoinService::validate($payment->getRemoteId()), 'TurtleCoin sendTo address is not valid.');
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
