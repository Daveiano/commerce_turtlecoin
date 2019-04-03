<?php

namespace Drupal\commerce_turtlecoin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TurtleCoinPaymentAddForm.
 *
 * Defines the Payment TurleCoinPayment.
 *
 * @package Drupal\commerce_payment_example\PluginForm\TurtleCoinPaymentAddForm
 */
class TurtlePayPaymentAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $order->getTotalPrice()->toArray(),
      '#required' => TRUE,
    ];
    $form['received'] = [
      '#type' => 'checkbox',
      '#title' => t('The specified amount was already received.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $values['received']);
  }

}
