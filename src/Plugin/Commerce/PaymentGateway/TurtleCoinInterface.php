<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
// @todo what are this interfaces for?
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface;

/**
 * Provides the interface for the TurtleCoin payment gateway.
 */
interface TurtleCoinInterface extends OnsitePaymentGatewayInterface {

}
