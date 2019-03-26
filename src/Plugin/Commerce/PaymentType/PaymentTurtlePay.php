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
    $fields['turtlepay_checkout_response'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtlePay Checkout Response'))
      ->setDescription(t('Response from TurtlePay while checkout.'))
      ->setRequired(TRUE);

    $fields['turtlepay_callback_secret'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtlePay Callback Secret'))
      ->setDescription(t('A secret string to validate the callback response.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
