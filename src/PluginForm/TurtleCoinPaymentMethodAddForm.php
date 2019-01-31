<?php

namespace Drupal\commerce_payment_example\PluginForm\TurtleCoinPaymentMethodAddForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TurtleCoinPaymentMethodAddForm.
 *
 * @package Drupal\commerce_payment_example\PluginForm\TurtleCoinPaymentMethodAddForm
 */
class TurtleCoinPaymentMethodAddForm extends PaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);
    $element['turtle_address']['#default_value'] = '';

    return $element;
  }

}
