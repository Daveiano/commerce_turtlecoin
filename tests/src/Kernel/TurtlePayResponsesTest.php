<?php

namespace Drupal\Tests\commerce_turtlecoin\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

class TurtlePayResponsesTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_exchanger',
    'commerce_exchanger_cryptocompare',
    'commerce_currency_resolver',
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_payment');
    $this->installConfig(['commerce_exchanger']);
    $this->installConfig(['commerce_exchanger_cryptocompare']);
    $this->installConfig(['commerce_turtlecoin']);
    $this->installConfig(['commerce_turtlecoin_test']);
  }

  // @todo Create a mock response from turtlePay and check payment
  //   transitions (every).
  // @todo Could be a kernel test?
  public function testPaymentTransition() {
    $order = $this->createOrderWithPayment();
    // TODO: Payment is null - $order->get('payment_method')->first() works.
    $payment = $order->get('payment_method')->getValue();
    $payment = Payment::load($payment[0]["target_id"]);
  }

  /**
   * Create an order with payment and mocked TurtlePay response.
   *
   * @todo Create trait for creating order and payment.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created commerce order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createOrderWithPayment() {
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
      'payment_gateway' => 'turtlepay',
    ]);
    $order->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = Payment::create([
      'type' => 'payment_turtle_pay',
      'payment_gateway' => 'turtlepay',
      'order_id' => $order->id(),
      'amount' => new Price(20, 'TRT'),
      'state' => 'pending',
    ]);
    $payment->save();

    $this->reloadEntity($payment);

    $order->set('payment_method', $payment->id());
    $order->save();

    // Create a mock response from turtlePay.
    $mock_response_body = [
      "paymentId" => "b46bc1a8fff7f0020b5057dd643a6ab3319d843850c788a4d90e1ebdb7b9c19f",
      "atomicAmount" => 2000,
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

}
