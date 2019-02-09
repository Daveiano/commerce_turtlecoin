<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the Turtle payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_turtlecoin_transaction",
 *   label = @Translation("TurleCoin"),
 *   create_label = @Translation("TurleCoin"),
 * )
 */
class TurtleCoinTransaction extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    // TODO: Implement buildLabel() method.
    return 'What does this?';
  }

}
