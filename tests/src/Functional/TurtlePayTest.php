<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_turtlecoin\TurtleCoinService;

/**
 * Tests turtlepay checkout and payment workflow.
 *
 * @todo Create every test also for turtleCoin.
 *
 * @group commerce_turtlecoin
 */
class TurtlePayTest extends CommerceBrowserTestBase {

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

  protected $product;

  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->store->set('default_currency', 'TRT');

    $this->placeBlock('commerce_checkout_progress');

    $this->variation = $this->createEntity('commerce_product_variation', [
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
      'variations' => [$this->variation],
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
    $this->assertSession()->pageTextContains($payment->getRemoteId());
  }

  // TODO: Create a mock response from turtlePay and check payment
  // transitions (every).
  public function testPaymentTransition() {
    $order = $this->createOrderWithPayment();
    // TODO: Payment is null - $order->get('payment_method')->first() works.
    $payment = $order->get('payment_method')->first()->referencedEntities();
    $test = Payment::load(1);
  }

  // TODO: Create helper method for creating order and payment.
  protected function createOrderWithPayment() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'title' => 'My product',
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => $this->variation->getPrice(),
    ]);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'payment_gateway' => 'turtlepay',
    ]);
    $order->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_turtle_pay',
      'payment_gateway' => 'turtlepay',
      'order_id' => $order->id(),
      'amount' => $this->variation->getPrice(),
      'state' => 'pending',
    ]);

    $payment->save();

    // TODO: Without this, when we load the order, there is no payment_method.
    $order->set('payment_method', $payment->id());
    $order->save();

    // Create a mock response from turtlePay.
    $mock_response_body = [
      "paymentId" => "b46bc1a8fff7f0020b5057dd643a6ab3319d843850c788a4d90e1ebdb7b9c19f",
      "atomicAmount" => 100000,
      "startHeight" => 1132510,
      "endHeight" => 1132570,
      "confirmations" => 30,
    ];

    $payment->setRemoteId('TRTLuyzE8L1HbJ8LWVQRr6J6MXV8JzWtw9uGQABuqgrCA6ZNGFpNAgKAQFQGoU9sUaAQQb6SQdDWt9GCoDFUxxBrHbHnWNpQFc4LYxeAEKhtWDYNS9Vd9dHVp3QHwjKbo76ggQKgUfVjQp8iPypECCy3MwZVyu89k1fWE2Ji6EKedbrqECHHWoyifd2');
    $payment->set('turtlepay_checkout_response', Json::encode($mock_response_body));
    $payment->set('turtlepay_callback_secret', Crypt::randomBytesBase64(96));

    $payment->save();

    $order = $this->reloadEntity($order);

    return $order;
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
