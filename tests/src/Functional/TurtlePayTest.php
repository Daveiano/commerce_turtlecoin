<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Drupal\Tests\commerce_turtlecoin\Traits\CommerceTrutlecoinOrderDataTrait;

/**
 * Tests turtlepay checkout and payment workflow.
 *
 * @todo Create every test also for turtleCoin.
 *
 * @group commerce_turtlecoin
 */
class TurtlePayTest extends CommerceBrowserTestBase {

  use CommerceTrutlecoinOrderDataTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_payment',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_exchanger',
    'commerce_exchanger_cryptocompare',
    'commerce_currency_resolver',
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
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
   * The product for the tests.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('commerce_currency_resolver.settings')
      ->set('currency_exchange_rates', 'cryptocompare')
      ->save();

    $this->store->set('default_currency', 'USD');
    $this->reloadEntity($this->store);

    $this->placeBlock('commerce_checkout_progress');

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createProduct($this->store);

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
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
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
    $this->assertSession()->pageTextContains('Please transfer the amount of 10525.43 TRTL to TRTL');
    $this->assertSession()->pageTextContains('Warning: This address will only be active for about 120 Blocks (2h)');

    // Assert some order values.
    $order = Order::load(1);
    $this->assertEquals('completed', $order->getState()->getId());
    $this->assertEquals('complete', $order->get('checkout_step')->first()->getValue()['value']);
    $this->assertEquals('turtlepay_payment_gateway', $order->get('payment_gateway')->entity->getPluginId());
    $this->assertEquals(new Price(1, 'USD'), $order->getTotalPrice());

    // Validate the created payment.
    $payment = Payment::load(1);
    $this->assertEquals(new Price(10525.43, 'TRT'), $payment->getAmount());
    $turtlepay_response = $payment->get('turtlepay_checkout_response')->value;
    $turtlepay_response = Json::decode($turtlepay_response);
    $this->assertNotEmpty($turtlepay_response, 'No response from TurtlePay.');
    $this->assertEquals('pending', $payment->getState()->getId());

    // Validate the address.
    $this->assertTrue(TurtleCoinService::validate($payment->getRemoteId()), 'TurtleCoin sendTo address is not valid.');
    $this->assertSession()->pageTextContains($payment->getRemoteId());
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
