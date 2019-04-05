<?php

namespace Drupal\commerce_turtlecoin\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\commerce_payment\PaymentStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TurtleCoin\TurtleService;
use GuzzleHttp\Exception\ConnectException;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "turtlecoin_payment_process_worker",
 *   title = @Translation("Turtle Coin Payment Process Worker")
 * )
 */
class TurtleCoinPaymentProcessWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * PaymentStorageInterface.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PaymentStorageInterface $payment_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->paymentStorage = $payment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('commerce_payment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if ($item->mode === 'debug') {
      \Drupal::logger('commerce_turtlecoin')->notice('Processing transaction with Payment ID: @payment_id.', [
        '@payment_id' => $item->paymentId,
      ]);
    }

    // Create a new turtle-service instance.
    $config = [
      'rpcHost' => $item->wallet_api_host,
      'rpcPort' => $item->wallet_api_port,
      'rpcPassword' => $item->wallet_api_password,
    ];

    $turtleService = new TurtleService($config);

    // Search for the given transaction.
    try {
      $transactions_response = $turtleService->getTransactions(
        $item->blockCount,
        $item->firstBlockIndex,
        NULL,
        [$item->turtlecoin_address_store],
        $item->paymentId
      )->toArray();

      // Get current block count for comparing max allowed wait time.
      $turtle_status = $turtleService->getStatus()->toArray();
      // Load the commerce_payment for amount comparing.
      $payment = $this->paymentStorage->loadByRemoteId($item->paymentId);

      if (count($transactions_response['result']['items']) > 0) {
        foreach ($transactions_response['result']['items'] as $transactions) {
          if (count($transactions['transactions']) > 0) {
            foreach ($transactions['transactions'] as $transaction) {
              if (($transaction['paymentId'] === $item->paymentId) && (floatval($transaction['amount']) === floatval(($payment->getAmount()->getNumber()) * 100))) {
                $tx_hash = $transaction['transactionHash'];

                return $this->completeTransaction($item->paymentId, $tx_hash);
              }
            }
          }
        }

        // No transaction found, proceed.
        return $this->checkIfTransactionOutdated($item, $turtle_status);
      }
      else {
        return $this->checkIfTransactionOutdated($item, $turtle_status);
      }
    }
    catch (ConnectException $connectException) {
      \Drupal::logger('commerce_turtlecoin')->error('Could not connect to Wallet RPC API: @error.', [
        '@error' => $connectException->getMessage(),
      ]);
    }
  }

  /**
   * Check if transaction is outdated. Transaction wait time is in seconds.
   *
   * TurtleCoin uses 1 block every 30 seconds, so this is quick mafs.
   *
   * @param object $item
   *   The queue worker item.
   * @param array $turtle_status
   *   Response from $turtleService->getStatus()->toArray().
   *
   * @return string|null
   *   Returns NULL if transaction is still active, 'voided' if not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function checkIfTransactionOutdated($item, array $turtle_status) {
    $transaction_wait_time = $item->wait_for_transactions_time;
    $transaction_wait_block_time = $transaction_wait_time / 30;

    if ($turtle_status['result']['blockCount'] > (intval($item->firstBlockIndex) + intval($transaction_wait_block_time))) {
      return $this->voidTransaction($item->paymentId);
    }
    else {
      return NULL;
    }
  }

  /**
   * Set a given payment to state 'complete'.
   *
   * @param string $payment_id
   *   PaymentId of transaction to process.
   * @param string $tx_hash
   *   The Transaction hash confirming the transaction.
   *
   * @return string
   *   Simple return 'complete' to indicate it's completed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function completeTransaction($payment_id, $tx_hash) {
    $payment = $this->paymentStorage->loadByRemoteId($payment_id);
    $payment->setState('completed');
    $payment->turtle_coin_tx_hash = $tx_hash;
    $payment->save();

    return 'completed';
  }

  /**
   * Set a given payment to state 'voided'.
   *
   * @param string $payment_id
   *   PaymentId of transaction to process.
   *
   * @return string
   *   Simple return 'voided' to indicate it's voided.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function voidTransaction($payment_id) {
    $payment = $this->paymentStorage->loadByRemoteId($payment_id);
    $payment->setState('voided');
    $payment->save();

    return 'voided';
  }

}
