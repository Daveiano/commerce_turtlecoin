<?php

namespace Drupal\Tests\commerce_turtlecoin\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Test TurtleCoinPaymentProcessWorker.
 *
 * @group commerce_turtlecoin
 */
class TurtlecoinPaymentProcessingTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_exchanger_cryptocompare',
    'commerce_exchanger',
    'commerce_currency_resolver',
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
  ];

  /**
   * Cron.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

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

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_payment_gateway');
    $this->installEntitySchema('commerce_exchange_rates');
    $this->installEntitySchema('commerce_currency');
    $this->installConfig(['commerce_exchanger_cryptocompare']);
    $this->installConfig(['commerce_exchanger', 'commerce_currency_resolver']);
    $this->installConfig('commerce_payment');
    $this->installConfig(['commerce_turtlecoin']);
    $this->installConfig(['commerce_turtlecoin_test']);

    $this->config('commerce_currency_resolver.settings')
      ->set('currency_exchange_rates', 'cryptocompare')
      ->set('currency_default', 'USD')
      ->save();

    $this->store->set('default_currency', 'USD');
    $this->reloadEntity($this->store);

    $this->cron = $this->container->get('cron');
    $this->queue = $this->container->get('queue')->get('turtlecoin_payment_process_worker');
  }

  /**
   * Test that a payment completes when transaction is going in.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPaymentComplete() {
    $payment = $this->createOrderWithPayment(1000);
    $order = $payment->getOrder();

    $this->assertEquals('pending', $payment->getState()->getId());
    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->assertFalse($order->isPaid());

    $item = (object) [
      'turtlecoin_address_store' => "TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM",
      'wallet_api_host' => '127.0.0.1',
      'wallet_api_port' => 8070,
      'wallet_api_password' => 'password',
      'wait_for_transactions_time' => 3600,
      'firstBlockIndex' => $payment->get('turtle_coin_block_index')->value,
      'paymentId' => $payment->getRemoteId(),
      'mode' => 'debug',
    ];
    $this->queue->createItem($item);

    $this->assertEquals(1, $this->queue->numberOfItems());

    $this->cron->run();

    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->assertEquals('completed', $payment->getState()->getId());
    $this->assertEquals('f470547c88e209052a2e97df5f6ea9be2fbf2973605abb0f2dff922f33a8905c', $payment->get('turtle_coin_tx_hash')->value);
    $this->assertTrue($order->isPaid());
  }

  /**
   * Test that a payment gets voided when no transaction is going in.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPaymentVoid() {
    $payment = $this->createOrderWithPayment(1500);

    $this->assertEquals('pending', $payment->getState()->getId());
    $this->assertEquals(0, $this->queue->numberOfItems());

    $item = (object) [
      'turtlecoin_address_store' => "TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM",
      'wallet_api_host' => '127.0.0.1',
      'wallet_api_port' => 8070,
      'wallet_api_password' => 'password',
      'wait_for_transactions_time' => 3600,
      'firstBlockIndex' => $payment->get('turtle_coin_block_index')->value,
      'paymentId' => $payment->getRemoteId(),
      'mode' => 'debug',
    ];
    $this->queue->createItem($item);

    $this->assertEquals(1, $this->queue->numberOfItems());

    $this->cron->run();

    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->assertEquals('voided', $payment->getState()->getId());
  }

  /**
   * Create an order with payment.
   *
   * @param int $blockIndex
   *   The current block index.
   *
   * @return \Drupal\commerce_payment\Entity\Payment
   *   The created commerce order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createOrderWithPayment(int $blockIndex): Payment {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'title' => 'My product',
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price(1, 'USD'),
    ]);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'payment_gateway' => 'turtlecoin',
    ]);
    $order->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_turtle_coin',
      'payment_gateway' => 'turtlecoin',
      'order_id' => $order->id(),
      'amount' => new Price(20, 'TRT'),
      'state' => 'pending',
    ]);
    $payment->save();

    $this->reloadEntity($payment);

    $order->set('payment_method', $payment->id());
    $order->save();

    $payment->setRemoteId('0aa63a0044724e208fd15a55c44689b98fd7063257f66bedf781fd826f514f5e');
    $payment->set('turtle_coin_integrated_address', 'TRTLuxiMvAh9bSvKP4vFcM9mJQFSG6KBE9vrJqzVdVWuAQSo5jfDW8E94iR7QciDPKA6bbLKm7caUJ8KtTSoVNiL9mLdKvMWns14jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU5LutLj');
    $payment->set('turtle_coin_block_index', $blockIndex);

    $payment->save();

    $order = $this->reloadEntity($order);

    $payment_id = $order->get('payment_method')->getValue()[0]["target_id"];
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = Payment::load($payment_id);

    return $payment;
  }

}
