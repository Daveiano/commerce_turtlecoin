<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the TurtleCoin onsite Checkout payment gateway.
 *
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/on-site-gateways
 *
 * @CommercePaymentGateway(
 *   id = "turtlecoin_payment",
 *   label = @Translation("TurtleCoin"),
 *   display_label = @Translation("TurtleCoin"),
 *   payment_method_types = {"turtlecoin"},
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_turtlecoin\PluginForm\TurtleCoinPaymentMethodAddForm",
 *   },
 * )
 */
class TurtleCoin extends OnsitePaymentGatewayBase implements TurtleCoinInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wallet' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['wallet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wallet address'),
      '#description' => $this->t('Your own Wallet address where the payments will come in.'),
      '#default_value' => $this->configuration['wallet'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['wallet'] = $values['wallet'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {

  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {

  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {

  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function updatePaymentMethod(PaymentMethodInterface $payment_method) {

  }

}
