<?php

namespace Drupal\commerce_turtlecoin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Class TurtleCoinPaymentMethodAddForm.
 *
 * Defines the Payment Method TurleCoinPayment.
 *
 * @package Drupal\commerce_payment_example\PluginForm\TurtleCoinPaymentMethodAddForm
 */
class TurtleCoinPaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'credit-card-form';

    $element['turtlecoin_address_customer'] = [
      '#type' => 'textfield',
      '#title' => t('TurtleCoin address'),
      '#description' => t('Please enter your TurtleCoin address.'),
      '#default_value' => '',
      '#maxlength' => 99,
      '#size' => 99,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!TurtleCoinBaseController::validate($values['turtlecoin_address_customer'])) {
      $form_state->setError($element['turtlecoin_address_customer'], t('You have entered an invalid TurtleCoin Address.'));
    }
  }

}
