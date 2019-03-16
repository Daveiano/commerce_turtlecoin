<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Provides the TurtleCoin Checkout payment gateway.
 *
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/on-site-gateways
 *
 * @CommercePaymentGateway(
 *   id = "turtlecoin_payment_gateway",
 *   label = @Translation("TurtleCoin"),
 *   display_label = @Translation("TurtleCoin"),
 *   payment_type = "payment_turtle_coin",
 *   forms = {
 *     "add-payment" = "Drupal\commerce_turtlecoin\PluginForm\TurtleCoinPaymentAddForm",
 *   },
 * )
 */
class TurtleCoin extends PaymentGatewayBase implements TurtleCoinInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'turtlecoin_address_store' => '',
      'wallet_api_host' => 'localhost',
      'wallet_api_port' => '8070',
      'wallet_api_password' => 'password',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // TODO: Do we need the address?
    $form['turtlecoin_address_store'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TurtleCoin address'),
      '#description' => $this->t('Your stores wallet address where the payments will come in.'),
      '#default_value' => $this->configuration['turtlecoin_address_store'],
      '#required' => TRUE,
    ];
    $form['wallet_api_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wallet-API host'),
      '#description' => $this->t('The host where your wallet-api is available.'),
      '#default_value' => $this->configuration['wallet_api_host'],
      '#required' => TRUE,
    ];
    $form['wallet_api_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wallet-API port'),
      '#description' => $this->t('The port where your wallet-api is available.'),
      '#default_value' => $this->configuration['wallet_api_port'],
      '#required' => TRUE,
    ];
    $form['wallet_api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wallet-API password'),
      '#description' => $this->t('The password the wallet-api uses to authorize.'),
      '#default_value' => $this->configuration['wallet_api_password'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      if (!TurtleCoinBaseController::validate($values['turtlecoin_address_store'])) {
        $form_state->setError($form['turtlecoin_address_store'], t('You have entered an invalid TurtleCoin Address.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['turtlecoin_address_store'] = $values['turtlecoin_address_store'];
    $this->configuration['wallet_api_host'] = $values['wallet_api_host'];
    $this->configuration['wallet_api_port'] = $values['wallet_api_port'];
    $this->configuration['wallet_api_password'] = $values['wallet_api_password'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    dsm('buildPaymentInstructions');

    $instructions = [
      '#type' => 'processed_text',
      '#text' => 'TEST Text',
      '#format' => 'plain_text',
    ];

    return $instructions;
  }

  /**
   * {@inheritdoc}
   *
   * Called when the Pay and complete purchase button has been clicked on the
   * final page of the checkout process: the Review page.
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE) {
    $this->assertPaymentState($payment, ['new']);
    /*$payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);*/
    $amount = $payment->getAmount();

    // Perform verifications related to billing address, payment currency, etc.
    // Throw exceptions as needed.
    // See \Drupal\commerce_payment\Exception for the available exceptions.

    // @todo Perform the create payment request here, throw an exception if it fails.
    // Remember to take into account $capture when performing the request.
    //$payment_method_token = $payment_method->getRemoteId();
    // The remote ID returned by the request.
    $remote_id = '123456';

    $payment->state = $received ? 'completed' : 'pending';
    $payment->save();
    //$payment->setRemoteId($remote_id);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   *
   * Called during the checkout process, when the Continue to review button
   * has been clicked on the Order information page.
   *
   * @see GoCardless
   */
  /*public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // Perform the create request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // You might need to do different API requests based on whether the
    // payment method is reusable: $payment_method->isReusable().
    // Non-reusable payment methods usually have an expiration timestamp.
    //$payment_method->turtlecoin_integrated_address_customer = $payment_details['turtlecoin_integrated_address_customer'];

    // Creates a reusable payment method.
    $payment_method->setReusable(FALSE);
    $payment_method->save();
  }*/

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {

  }

}
