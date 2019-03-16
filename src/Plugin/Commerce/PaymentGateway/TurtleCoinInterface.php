<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
// @todo what are this interfaces for?
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface;

/**
 * Provides the interface for the TurtleCoin payment gateway.
 *
 * TODO: PaymentGatewayInterface vs OnsitePaymentGatewayInterface.
 */
interface TurtleCoinInterface extends PaymentGatewayInterface, HasPaymentInstructionsInterface, SupportsVoidsInterface {

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param bool $received
   *   Whether the payment was already received.
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE);

}
