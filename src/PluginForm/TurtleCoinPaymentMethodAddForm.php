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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'commerce_turtlecoin_transaction') {
      $form['payment_details'] = $this->buildTurtleCoinTransactionForm($form['payment_details'], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'commerce_turtlecoin_transaction') {
      $this->validateTurtleCoinTransactionForm($form['payment_details'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $payment_method = $this->entity;

    if ($payment_method->bundle() == 'commerce_turtlecoin_transaction') {
      $this->submitTurtleCoinTransactionForm($form['payment_details'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTurtleCoinTransactionForm(array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'turtlecoin-transaction-form';

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
  protected function validateTurtleCoinTransactionForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (!TurtleCoinBaseController::validate($values['turtlecoin_address_customer'])) {
      $form_state->setError($element['turtlecoin_address_customer'], t('You have entered an invalid TurtleCoin Address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function submitTurtleCoinTransactionForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    $this->entity->turtlecoin_address_customer = $values['turtlecoin_address_customer'];
  }

}
