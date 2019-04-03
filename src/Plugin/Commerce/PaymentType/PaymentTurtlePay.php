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
 *   workflow = "payment_turtlepay",
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

    $fields['turtlepay_callback_response'] = BundleFieldDefinition::create('string_long')
      ->setLabel(t('TurtlePay Callback Response'))
      ->setDescription(t('The Json response from TurtlePay.'))
      ->setRequired(TRUE);

    $fields['turtlepay_tx_hash'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtlePay Transaction Hash'))
      ->setDescription(t('Transaction Hash for the transaction from TurtlePay to your wallet.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
