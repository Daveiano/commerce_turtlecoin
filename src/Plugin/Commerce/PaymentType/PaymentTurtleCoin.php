<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the turtle_coin payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_turtle_coin",
 *   label = @Translation("TurtleCoin"),
 * )
 */
class PaymentTurtleCoin extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
