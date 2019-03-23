<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the turtle_coin payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_turtle_pay",
 *   label = @Translation("TurtlePay"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentTurtlePay extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // TODO: How to save the whole response?
    $fields['turtlepay_checkout_response'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtlePay Checkout Response'))
      ->setDescription(t('Response from TurtlePay while checkout.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
