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

    ddl($_SERVER);
    ddl(Json::decode($request->getContent(), TRUE));

    if ($payment && $payment->get('turtlepay_callback_secret')->value === $secret) {
      $data = Json::decode($request->getContent(), TRUE);

      // Save the callback response json to the payment.
      $payment->turtlepay_callback_response = $request->getContent();

      // @see https://docs.turtlepay.io/api/.
      switch ($data['status']) {
        case 408:
          // walletTimeout.
          // Called when the wallet created for the request times out as no
          // funds were received into the wallet within the allowed time.
          $payment->setState('voided');
          $payment->save();
          break;

        case 402:
          // notEnoughFunds.
          // Called when we have received funds for the request; however, there
          // are not enough funds available to forward the funds (due to the
          // network transaction fee) to forward the funds to the requested
          // wallet.
          $payment->setState('partially_payed');
          $payment->save();
          break;

        case 200:
          // sentFunds.
          // Called when we relay funds to the specified wallet.
          $payment->setState('sent');
          $payment->save();
          break;

        case 102:
          // inProgress.
          // Called when we have received funds for the request; however, we
          // have not received all the funds requested or the funds have not
          // reached the required confirmation depth.
          $payment->setState('in_progress');
          $payment->save();
          break;

        case 100:
          // receivedFunds.
          // Called when at least the requested funds have been sent to
          // the specified wallet and enough confirmations have completed.
          $payment->setState('completed');
          $payment->save();
          break;
      }

      return new JsonResponse(['success' => TRUE]);
    }
    else {
      return new JsonResponse(['success' => FALSE]);
    }
  }

}
