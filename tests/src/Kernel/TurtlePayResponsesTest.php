<?php

namespace Drupal\Tests\commerce_turtlecoin\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test TurtlePay responses.
 *
 * @see https://docs.turtlepay.io/api/
 *
 * @group commerce_turtlecoin
 */
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
   * The Tx.
   *
   * @var string
   */
  protected $finalTransactionHash;

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

    $this->finalTransactionHash = 'bdb052ef739064650239e53b34572a7f5d6103a7f68283d3218d306c4a77400a';
  }

  /**
   * Test void transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testPaymentTransitionVoided() {
    $payment = $this->createOrderWithPayment();
    $this->assertEquals('pending', $payment->getState()->getId());

    $response_body = $this->createTurtlePayResponseMockRequest($payment, 408);

    $this->assertEquals(['success' => TRUE], $response_body);
    $this->assertEquals('voided', $payment->getState()->getId());
  }

  /**
   * Test partially_payed transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testPaymentTransitionPartiallyPaid() {
    $payment = $this->createOrderWithPayment();
    $this->assertEquals('pending', $payment->getState()->getId());

    $response_body = $this->createTurtlePayResponseMockRequest($payment, 402);

    $this->assertEquals(['success' => TRUE], $response_body);
    $this->assertEquals('partially_payed', $payment->getState()->getId());
  }

  /**
   * Test in_progress transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testPaymentTransitionInProgress() {
    $payment = $this->createOrderWithPayment();
    $this->assertEquals('pending', $payment->getState()->getId());

    $response_body = $this->createTurtlePayResponseMockRequest($payment, 102);

    $this->assertEquals(['success' => TRUE], $response_body);
    $this->assertEquals('in_progress', $payment->getState()->getId());
  }

  /**
   * Test sent transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testPaymentTransitionSent() {
    $payment = $this->createOrderWithPayment();
    $this->assertEquals('pending', $payment->getState()->getId());

    $response_body = $this->createTurtlePayResponseMockRequest($payment, 100);

    $this->assertEquals(['success' => TRUE], $response_body);
    $this->assertEquals('sent', $payment->getState()->getId());
  }

  /**
   * Test completed transition.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function testPaymentTransitionCompleted() {
    $payment = $this->createOrderWithPayment();
    $this->assertEquals('pending', $payment->getState()->getId());

    $response_body = $this->createTurtlePayResponseMockRequest($payment, 200);

    $this->assertEquals(['success' => TRUE], $response_body);
    $this->assertEquals('completed', $payment->getState()->getId());
    $this->assertEquals($this->finalTransactionHash, $payment->get('turtlepay_tx_hash')->getValue()[0]['value']);
  }

  /**
   * Create a TurtlePay response mock and post it to the Controller.
   *
   * @param \Drupal\commerce_payment\Entity\Payment $payment
   *   The commerce payment.
   * @param int $status
   *   The response status.
   *
   * @return mixed
   *   Requests response body.
   *
   * @throws \Exception
   */
  protected function createTurtlePayResponseMockRequest(Payment $payment, int $status) {
    $data = [
      "address" => "TRTLuxnZiWkAEhvAoGSkNEHG3aKS5db1WHnoxarheb4M9jLArMU59y2HxWzuyvsXCDHvvfk7c8dZSAZquLJiyv5f96P9kTcj1BM1mUMQf5KdydAh7ewz1GrHsJVpYiWkonuRUCRWSuWUMkfL6p7a7g3Eq5N1FEctyxhv41S3cwC72caRfaAhMipghbt",
      "paymentId" => "376178ab0113a5ae930eda9e9de2419ede7aead3e8fbe7b7e65191de15f20b63",
      "status" => $status,
      "request" => [
        "address" => "TRTLuxN6FVALYxeAEKhtWDYNS9Vd9dHVp3QHwjKbo76ggQKgUfVjQp8iPypECCy3MwZVyu89k1fWE2Ji6EKedbrqECHHWouZN6g",
        "amount" => 100,
        "userDefined" => [],
      ],
      "amount" => 100,
    ];

    if ($status === 200) {
      $data = $data + [
        'transactions' => [[$this->finalTransactionHash]],
      ];
    }

    $request = Request::create(
      '/commerce_turtlecoin/api/v1/turtlepay/' . $payment->get('turtlepay_callback_secret')->value . '/' . $payment->id(),
      'POST',
      [],
      [],
      [],
      [],
      Json::encode($data)
    );
    $request->headers->set('Content-type', 'application/json');
    $request->headers->set('Accept', 'application/json');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);
    $response_body = Json::decode($response->getContent());

    return $response_body;
  }

  /**
   * Create an order with payment and mocked TurtlePay response.
   *
   * @return \Drupal\commerce_payment\Entity\Payment
   *   The created commerce order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createOrderWithPayment(): Payment {
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

    $payment_id = $order->get('payment_method')->getValue()[0]["target_id"];
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = Payment::load($payment_id);

    return $payment;
  }

}
