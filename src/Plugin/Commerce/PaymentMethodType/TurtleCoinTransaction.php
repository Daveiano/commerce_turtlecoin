<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\entity\BundleFieldDefinition;

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
   *
   * Gets shown in the Payment information payment pane.
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('TurtleCoin Address for payment used:');
  }

  /**
   * {@inheritdoc}
   *
   * @todo does this config produce the strange table 'commerce_payment_method_358...'?
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    /*$fields['turtlecoin_address_customer'] = BundleFieldDefinition::create('string')
      ->setLabel(t('TurtleCoin address'))
      ->setDescription(t('The user-entered TRTL address.'))
      ->setRequired(TRUE);*/

    return $fields;
  }

}
