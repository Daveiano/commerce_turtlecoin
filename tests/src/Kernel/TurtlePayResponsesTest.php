<?php

namespace Drupal\Tests\commerce_turtlecoin\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
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
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

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

    $this->httpClient = $this->container->get('http_client');
  }

  /**
   * @todo Create a mock response from turtlePay and check payment
   *  transitions (every).
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPaymentTransition() {
    $order = $this->createOrderWithPayment();
    $payment_id = $order->get('payment_method')->getValue()[0]["target_id"];
    $payment = Payment::load($payment_id);

    $data = Json::encode([
      "address" => "TRTLuxnZiWkAEhvAoGSkNEHG3aKS5db1WHnoxarheb4M9jLArMU59y2HxWzuyvsXCDHvvfk7c8dZSAZquLJiyv5f96P9kTcj1BM1mUMQf5KdydAh7ewz1GrHsJVpYiWkonuRUCRWSuWUMkfL6p7a7g3Eq5N1FEctyxhv41S3cwC72caRfaAhMipghbt",
      "paymentId" => "376178ab0113a5ae930eda9e9de2419ede7aead3e8fbe7b7e65191de15f20b63",
      "status" => 100,
      "request" => [
        "address" => "TRTLuxN6FVALYxeAEKhtWDYNS9Vd9dHVp3QHwjKbo76ggQKgUfVjQp8iPypECCy3MwZVyu89k1fWE2Ji6EKedbrqECHHWouZN6g",
        "amount" => 100,
        "userDefined" => [],
      ],
      "amount" => 100,
    ]);

    $test = \Drupal::request()->getUri();

    $response = $this->httpClient->post(\Drupal::request()->getSchemeAndHttpHost() . '/commerce_turtlecoin/api/v1/turtlepay/' . $payment->get('turtlepay_callback_secret')->value . '/' . $payment_id, [
      'headers' => [
        'Content-type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'body' => $data,
    ]);

    $response_body = Json::decode($response->getBody()->getContents());
  }

  /**
   * Create an order with payment and mocked TurtlePay response.
   *
   * @todo Create trait for creating order and payment.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The created commerce order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createOrderWithPayment(): OrderInterface {
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
