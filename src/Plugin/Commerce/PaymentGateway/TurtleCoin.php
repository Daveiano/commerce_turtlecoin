<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;
use TurtleCoin\TurtleService;

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
 *   forms = {
 *     "add-payment" = "Drupal\commerce_turtlecoin\PluginForm\TurtleCoinPaymentAddForm",
 *     "receive-payment" = "Drupal\commerce_turtlecoin\PluginForm\TurtleCoinPaymentReceiveForm",
 *   },
 *   payment_type = "payment_turtle_coin",
 * )
 */
class TurtleCoin extends PaymentGatewayBase implements TurtleCoinInterface {

  protected $turtleService;

  /**
   * TurtleCoin constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $config = [
      'rpcHost' => $configuration['wallet_api_host'],
      'rpcPort' => $configuration['wallet_api_port'],
      'rpcPassword' => $configuration['wallet_api_password'],
    ];

    $this->turtleService = new TurtleService($config);
  }

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

    // TODO: Show a few status information.

    // TODO: Remove debug output.
    /*$response = $this->turtleService->getTransactions(
      100,
      1329390,
      NULL,
      ['TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1'],
      '2379289011096fc112b8aaf9a94101c97cf3de375eb534aa17d9a62fa230a084'
    )->toArray();

    dsm($response['result']['items']);*/

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
    // TODO: Prettify.
    $instructions = [
      '#type' => 'processed_text',
      '#text' => 'Please transfer the amount of ' . $payment->getAmount() . ' to ' . $payment->get('turtle_coin_integrated_address')->value,
      '#format' => 'plain_text',
    ];

    return $instructions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->getId();
    $operations = [];
    $operations['receive'] = [
      'title' => $this->t('Receive'),
      'page_title' => $this->t('Receive payment'),
      'plugin_form' => 'receive-payment',
      'access' => $payment_state == 'pending',
    ];
    $operations['void'] = [
      'title' => $this->t('Void'),
      'page_title' => $this->t('Void payment'),
      'plugin_form' => 'void-payment',
      'access' => $payment_state == 'pending',
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   *
   * Called when the Pay and complete purchase button has been clicked on the
   * final page of the checkout process: the Review page.
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE) {
    // TODO: Put in try catch?
    // Perform verifications related to billing address, payment currency, etc.
    // Throw exceptions as needed.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $this->assertPaymentState($payment, ['new']);

    // Create an integrated address for better transaction mapping via
    // integrated payment id.
    // TODO: Is this the way to generate a payment ID?
    $turtlecoin_payment_id = bin2hex(openssl_random_pseudo_bytes(32));
    $integrated_address = $this->turtleService->createIntegratedAddress(
      $this->getConfiguration()['turtlecoin_address_store'],
      $turtlecoin_payment_id
    )->toArray();

    // Add the integrated address to the payment.
    $payment->turtle_coin_integrated_address = $integrated_address['result']['integratedAddress'];

    // Get the current block index and save it to the transaction.
    $turtle_status = $this->turtleService->getStatus()->toArray();
    $payment->turtle_coin_block_index = $turtle_status['result']['blockCount'];

    $payment->setRemoteId($turtlecoin_payment_id);
    $payment->state = $received ? 'completed' : 'pending';
    $payment->save();

    // Add the payment to the worker queue.
    $queue = \Drupal::queue('turtlecoin_payment_process_worker');

    $item = (object) [
      'turtlecoin_address_store' => $this->getConfiguration()['turtlecoin_address_store'],
      'wallet_api_host' => $this->getConfiguration()['wallet_api_host'],
      'wallet_api_port' => $this->getConfiguration()['wallet_api_port'],
      'wallet_api_password' => $this->getConfiguration()['wallet_api_password'],
      'firstBlockIndex' => '',
      'blockCount' => 100,
      'paymentId' => $turtlecoin_payment_id,
    ];

    $queue->createItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['pending']);

    // If not specified, use the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $payment->state = 'completed';
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['pending']);

    $payment->state = 'voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Display not supported.
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

}
