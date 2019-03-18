<?php

namespace Drupal\commerce_turtlecoin\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\commerce_payment\PaymentStorage;
use TurtleCoin\TurtleService;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "turtlecoin_payment_process_worker",
 *   title = @Translation("Turtle Coin Payment Process Worker")
 * )
 */
class TurtleCoinPaymentProcessWorker extends QueueWorkerBase {

  protected $paymentStorage;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, PaymentStorage $paymentStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->paymentStorage = $paymentStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    \Drupal::logger('commerce_turtlecoin')->notice('Processing transaction with Payment ID: %payment_id.',
      array(
        '@payment_id' => $item->payment_id,
      ));

    $config = [
      'rpcHost' => $item->wallet_api_host,
      'rpcPort' => $item->wallet_api_port,
      'rpcPassword' => $item->wallet_api_password,
    ];

    $turtleService = new TurtleService($config);

    $transactions = $turtleService->getTransactions(
      $item->blockCount,
      $item->firstBlockIndex,
      NULL,
      [$item->turtlecoin_address_store],
      $item->payment_id
    );

    // TODO: Validate response.
    if (count($transactions['result']['items']) > 0) {

    }
    else {
      // TODO: Re-Add the item to the queue.
      $this->createItem($item);
    }
  }

  public function completeTransaction($transaction) {
    $payment = $this->paymentStorage->loadByRemoteId($transaction->payment_id);

    $payment->setState('completed');
  }

  public function voidTransaction($transaction) {
    $payment = $this->paymentStorage->loadByRemoteId($transaction->payment_id);

    $payment->setState('voided');
  }

}
