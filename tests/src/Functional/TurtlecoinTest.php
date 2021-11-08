<?php

namespace Drupal\Tests\commerce_turtlecoin\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Drupal\Tests\commerce_turtlecoin\Traits\CommerceTurtlecoinOrderDataTrait;
use GuzzleHttp\MessageFormatter;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Concat\Http\Middleware\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Tests turtlecoin checkout and payment workflow.
 *
 * @group commerce_turtlecoin
 */
class TurtlecoinTest extends CommerceBrowserTestBase {

  use CommerceTurtlecoinOrderDataTrait;

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
   * The turtlecoin_payment_process_worker queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;


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

    $turtle_pay_gateway = PaymentGateway::load('turtlepay');
    $turtle_pay_gateway->delete();

    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer commerce_payment_gateway',
    ]);

    $this->queue = $this->container->get('queue')->get('turtlecoin_payment_process_worker');

    $this->drupalLogin($this->user);
  }

  /**
   * Tests the checkout process with Turtlecoin.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testTurtlecoinCheckout() {
    $this->assertEquals(0, $this->queue->numberOfItems());

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
    $this->assertSession()->pageTextContains('Turtlecoin');
    $this->submitForm([], 'Pay and complete purchase');

    // Assert payment instructions.
    $this->assertSession()->pageTextContains('Please transfer the amount of 10525.43 TRTL to TRTL');
    $this->assertSession()->pageTextContains('Warning: This address will only be active for about 120 Blocks (1h)');

    // Assert some order values.
    $order = Order::load(1);
    $this->assertEquals('completed', $order->getState()->getId());
    $this->assertEquals('complete', $order->get('checkout_step')->first()->getValue()['value']);
    $this->assertEquals('turtlecoin_payment_gateway', $order->get('payment_gateway')->entity->getPluginId());
    $this->assertEquals(new Price(1, 'USD'), $order->getTotalPrice());

    // Validate the created payment.
    $payment = Payment::load(1);
    $this->assertEquals(new Price(10525.43, 'TRT'), $payment->getAmount());
    $block_index = $payment->get('turtle_coin_block_index')->value;
    $this->assertEquals('455956', $block_index);
    $this->assertEquals('pending', $payment->getState()->getId());

    // Validate the address.
    $address = $payment->get('turtle_coin_integrated_address')->value;
    $this->assertTrue(TurtleCoinService::validate($address), 'TurtleCoin sendTo address is not valid.');
    $this->assertSession()->pageTextContains($address);

    // Check the added queue item.
    $this->assertEquals(1, $this->queue->numberOfItems());
    $item = $this->queue->claimItem();
    $this->assertNotEmpty($item);
    $this->assertEquals('TRTLuxN6FVALYxeAEKhtWDYNS9Vd9dHVp3QHwjKbo76ggQKgUfVjQp8iPypECCy3MwZVyu89k1fWE2Ji6EKedbrqECHHWouZN6g', $item->data->turtlecoin_address_store);
    $this->assertEquals('http://localhost', $item->data->wallet_api_host);
    $this->assertEquals('8070', $item->data->wallet_api_port);
    $this->assertEquals('password', $item->data->wallet_api_password);
    $this->assertEquals(3600, $item->data->wait_for_transactions_time);
    $this->assertEquals('455956', $item->data->firstBlockIndex);
    $this->assertEquals(100, $item->data->blockCount);
    $this->assertEquals('debug', $item->data->mode);
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
