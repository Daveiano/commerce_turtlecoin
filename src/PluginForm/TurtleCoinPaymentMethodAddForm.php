<?php

namespace Drupal\commerce_turtlecoin\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Class TurtleCoinPaymentMethodAddForm.
 *
 * Defines the Payment Method TurleCoinPayment.
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

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!TurtleCoinBaseController::validate($values['turtle_address'])) {
      $form_state->setError($element['turtle_address'], t('You have entered an invalid TurtleCoin Address.'));
    }
  }

}
