<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the Turtle payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "turtle_coin_transaction",
 *   label = @Translation("Turtle"),
 *   create_label = @Translation("New Turtle transaction"),
 * )
 */
class TurtleCoinTransaction extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    // TODO: Implement buildLabel() method.

  }

}
