<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the turtle_coin payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_turtle_coin",
 *   label = @Translation("TurtleCoin"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentTurtleCoin extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields['turtle_coin_integrated_address'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtleCoin Integrated address'))
      ->setDescription(t('The Integrated address used for the transaction.'))
      ->setRequired(TRUE);

    $fields['turtle_coin_block_index'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Block Index'))
      ->setDescription(t('Turtle Chain Block index at the time of purchase.'))
      ->setRequired(TRUE);

    return $fields;
  }

}