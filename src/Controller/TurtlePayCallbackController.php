<?php

namespace Drupal\commerce_turtlecoin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\PaymentStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;

/**
 * Class TurtlePayCallbackController.
 */
class TurtlePayCallbackController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * PaymentStorageInterface.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(PaymentStorageInterface $payment_storage) {
    $this->paymentStorage = $payment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('commerce_payment')
    );
  }

  /**
   * Callback for TurtlePay.
   *
   * TODO: Validate as much as possible.
   *
   * @param string $secret
   *   A secret and unique string to validate the response.
   * @param int $payment_id
   *   The id of the payment to update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Simple json response to indicate the status of the information.
   */
  public function postProcess($secret, $payment_id, Request $request) {
    // First check if there is a payment with the specified id and secret.
    $payment = $this->paymentStorage->load($payment_id);

    if ($payment && $payment->get('turtlepay_callback_secret')->value === $secret) {
      $data = Json::decode($request->getContent());

      // TODO: Validate the response.

      ddl($data);
      ddl($secret);
      ddl($payment_id);
      ddl($request->getClientIp());
      ddl($_SERVER);
      ddl($_SERVER['HTTP_ORIGIN']);
      ddl($_SERVER['REMOTE_ADDR']);
      ddl($_SERVER['SERVER_ADDR']);

      return new JsonResponse(['success' => TRUE]);
    }
    else {
      return new JsonResponse(['success' => FALSE]);
    }
  }

}
