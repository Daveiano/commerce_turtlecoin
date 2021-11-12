<?php

namespace Drupal\commerce_turtlecoin\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_exchanger\ExchangerCalculatorInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use GuzzleHttp\ClientInterface;
use Drupal\commerce_price\Price;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Crypt;

/**
 * Provides the TurtleCoin Checkout payment gateway.
 *
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway
 * @see https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/on-site-gateways
 *
 * @CommercePaymentGateway(
 *   id = "turtlepay_payment_gateway",
 *   label = @Translation("TurtlePay"),
 *   display_label = @Translation("TurtlePay"),
 *   forms = {
 *     "add-payment" = "Drupal\commerce_turtlecoin\PluginForm\TurtlePayPaymentAddForm",
 *     "receive-payment" = "Drupal\commerce_turtlecoin\PluginForm\TurtlePayPaymentReceiveForm",
 *   },
 *   modes = {
 *     "debug" = "Debug",
 *     "live" = "Live",
 *   },
 *   payment_type = "payment_turtle_pay",
 *   requires_billing_information = FALSE,
 * )
 */
class TurtlePay extends PaymentGatewayBase implements TurtlePayInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Turtle Coin service.
   *
   * @var \Drupal\commerce_turtlecoin\TurtleCoinService
   */
  protected $turtleCoinService;

  /**
   * Price Calculator.
   *
   * @var \Drupal\commerce_exchanger\ExchangerCalculatorInterface
   */
  protected $calculator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('http_client'),
      $container->get('commerce_turtlecoin.turtle_coin_service'),
      $container->get('commerce_currency_resolver.calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ClientInterface $http_client, TurtleCoinService $turtle_coin_service, ExchangerCalculatorInterface $calculator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->httpClient = $http_client;
    $this->turtleCoinService = $turtle_coin_service;
    $this->calculator = $calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'turtlecoin_address_store' => '',
      'turtlecoin_private_view_key' => '',
      'turtlepay_callback_host' => 'https://yourdomain.com',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['turtlecoin_address_store'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TurtleCoin address'),
      '#description' => $this->t('Your stores wallet address where the payments will come in.'),
      '#default_value' => $this->configuration['turtlecoin_address_store'],
      '#required' => TRUE,
    ];

    $form['turtlecoin_private_view_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TurtleCoin private View Key'),
      '#description' => $this->t('The Private View Key of the wallet address above (view only access).'),
      '#default_value' => $this->configuration['turtlecoin_private_view_key'],
      '#required' => TRUE,
    ];

    $form['turtlepay_callback_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TurtlePay Callback Host'),
      '#description' => $this->t('TurtlePay will send status updates to this host.'),
      '#default_value' => $this->configuration['turtlepay_callback_host'],
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

      if (!$this->turtleCoinService->validate($values['turtlecoin_address_store'])) {
        $form_state->setError($form['turtlecoin_address_store'], t('You have entered an invalid TurtleCoin Address.'));
      }

      if (!UrlHelper::isValid($values['turtlepay_callback_host'], TRUE)) {
        $form_state->setError($form['turtlepay_callback_host'], t('You have entered an invalid Host.'));
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
    $this->configuration['turtlecoin_private_view_key'] = $values['turtlecoin_private_view_key'];
    // Remove ending slash from host input.
    $this->configuration['turtlepay_callback_host'] = rtrim($values['turtlepay_callback_host'], '/');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    $response = $payment->get('turtlepay_checkout_response')->value;
    $response = Json::decode($response);
    $validity_time = $response['endHeight'] - $response['startHeight'];

    $instructions = [
      '#theme' => 'turtlecoin_turtle_pay_payment_instructions',
      '#payment_amount' => $payment->getAmount()->__toString(),
      '#payment_amount_atomic' => $payment->getAmount()->multiply(100)->getNumber(),
      '#turtle_address' => $payment->getRemoteId(),
      '#validity_time' => $validity_time / 60,
      '#validity_time_blocks' => $validity_time,
      '#attached' => [
        'library' => ['commerce_turtlecoin/payment_instructions'],
      ],
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
    // Perform verifications related to billing address, payment currency, etc.
    // Throw exceptions as needed.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $this->assertPaymentState($payment, ['new']);
    // Save the payment to get it's ID for the callback.
    $payment->setAmount($this->calculator->priceConversion($payment->getAmount(), $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO));
    $payment->save();

    // Generate a secret and unique string for the callback url.
    $secret = Crypt::randomBytesBase64(96);
    $payment_amount = floatval($payment->getAmount()->multiply(100)->getNumber());

    $data = Json::encode([
      // @see https://docs.turtlepay.io/api/
      'amount' => intval($payment_amount),
      'address' => $this->getConfiguration()['turtlecoin_address_store'],
      'privateViewKey' => $this->getConfiguration()['turtlecoin_private_view_key'],
      'callback' => $this->getConfiguration()['turtlepay_callback_host'] . '/commerce_turtlecoin/api/v1/turtlepay/' . $secret . '/' . $payment->id(),
      'userDefined' => [
        'debug' => $this->getConfiguration()['mode'] === 'debug',
      ],
    ]);

    // Perform a request to TurtlePay API.
    try {
      $response = $this->httpClient->post("https://api.turtlepay.io/v2/new", [
        'headers' => [
          'Content-type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'body' => $data,
      ]);

      $response_body = Json::decode($response->getBody()->getContents());

      // Save the response to the payment and the sendToAddress as remote_id.
      $payment->setRemoteId($response_body['sendToAddress']);

      $turtlepay_checkout_response = $response_body;

      // Unset qrCode and sendToAddress, we save it separately -
      // could generate it by ourselves.
      unset($turtlepay_checkout_response['sendToAddress']);
      unset($turtlepay_checkout_response['qrCode']);
      unset($turtlepay_checkout_response['callbackPublicKey']);
      $payment->set('turtlepay_checkout_response', Json::encode($turtlepay_checkout_response));

      // Save secret to payment.
      $payment->set('turtlepay_callback_secret', $secret);

      $payment->state = $received ? 'completed' : 'pending';
      $payment->save();
    }
    catch (RequestException $e) {
      throw new PaymentGatewayException('Could not create TurtlePay payment. Message: ' . $e->getMessage(), $e->getCode(), $e);
    }
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
    // @todo Does this make sense?
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
