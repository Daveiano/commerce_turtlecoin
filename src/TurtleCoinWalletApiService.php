<?php

namespace Drupal\commerce_turtlecoin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use TurtleCoin\Http\JsonResponse;

/**
 * Service to use Turtlecoin wallet-api.
 *
 * @see https://turtlecoin.github.io/wallet-api-docs/
 */
class TurtleCoinWalletApiService {

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Wallet config.
   *
   * @var array
   */
  protected $config;

  /**
   * The API host.
   *
   * @var string
   */
  protected $host;

  /**
   * The API port.
   *
   * @var string
   */
  protected $port;

  /**
   * The API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Constructs a new TurtleCoinWalletApiService.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(Client $client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $client;

    $this->config = Settings::get('turtlecoin_wallet_config', [
      "daemonHost" => "127.0.0.1",
      "daemonPort" => 11898,
      "filename" => "mywallet.wallet",
      "password" => "password",
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $entity_type_manager->getStorage('commerce_payment_gateway')->loadByProperties([
      'plugin' => 'turtlecoin_payment_gateway',
    ]);
    $payment_gateway = reset($payment_gateway);
    $payment_gateway_configuration = $payment_gateway ? $payment_gateway->getPluginConfiguration() : [
      'wallet_api_host' => '127.0.0.1',
      'wallet_api_port' => 8070,
      'wallet_api_password' => 'password',
    ];

    $this->host = $payment_gateway_configuration["wallet_api_host"];
    $this->port = $payment_gateway_configuration["wallet_api_port"];
    $this->apiKey = $payment_gateway_configuration["wallet_api_password"];

    // Initialize the wallet-api and open a wallet.
    try {
      $this->status();
    }
    catch (ConnectException $connectException) {
      // Wallet-api is not started.
      \Drupal::messenger()->addMessage(t('Could not connect to wallet-api: %message', ['%message' => $connectException->getMessage()]), 'error');
    }
    catch (RequestException $requestException) {
      // It seems that no wallet is yet opened.
      $message = $requestException->getMessage();

      if (strpos($message, 'Wallet file must be open') !== FALSE) {
        try {
          $this->rpcPost('wallet_open', $this->config);
        }
        catch (RequestException $walletOpenException) {
          \Drupal::messenger()->addMessage(t('Could not open wallet in wallet-api: %message', ['%message' => $walletOpenException->getMessage()]), 'error');
        }
      }
      else {
        \Drupal::messenger()->addMessage(t('Exception while connecting to wallet-api: %message', ['%message' => $requestException->getMessage()]), 'error');
      }
    }
  }

  /**
   * The /status request.
   *
   * @return \TurtleCoin\Http\JsonResponse
   *   The response.
   */
  public function status(): JsonResponse {
    return $this->rpcGet('status');
  }

  /**
   * The /addresses/{address}/{paymentID} request.
   *
   * @param string $address
   *   The wallets address.
   * @param string $paymentId
   *   The payment id.
   *
   * @return \TurtleCoin\Http\JsonResponse
   *   The response.
   */
  public function createIntegratedAddress(string $address, string $paymentId): JsonResponse {
    return $this->rpcGet('createIntegratedAddress', [
      'address' => $address,
      'paymentId' => $paymentId,
    ]);
  }

  /**
   * The /transactions/address/{address}/{startHeight} request.
   *
   * @param string $address
   *   The wallets address.
   * @param int $start_height
   *   First block count to look for.
   *
   * @return \TurtleCoin\Http\JsonResponse
   *   The response.
   */
  public function getTransactions(string $address, int $start_height): JsonResponse {
    return $this->rpcGet('getTransactions', [
      'address' => $address,
      'startHeight' => $start_height,
    ]);
  }

  /**
   * Get helper method.
   *
   * @param string $method
   *   Method.
   * @param array $params
   *   Params.
   *
   * @return \TurtleCoin\Http\JsonResponse
   *   The response.
   */
  public function rpcGet(string $method, array $params = []):JsonResponse {
    switch ($method) {
      case 'status':
        $rpcUri = $this->getRpcUri() . "/status";
        break;

      case 'createIntegratedAddress':
        $rpcUri = $this->getRpcUri() . "/addresses/" . $params['address'] . '/' . $params['paymentId'];
        break;

      case 'getTransactions':
        $rpcUri = $this->getRpcUri() . "/transactions/address/" . $params['address'] . '/' . $params['startHeight'];
        break;
    }

    $response = $this->httpClient->get($rpcUri, [
      'headers' => [
        'X-API-KEY' => $this->apiKey,
      ],
    ]);

    return new JsonResponse($response);
  }

  /**
   * Post helper method.
   *
   * @param string $method
   *   Method.
   * @param array $params
   *   Post body.
   *
   * @return \TurtleCoin\Http\JsonResponse
   *   The response.
   */
  public function rpcPost(string $method, array $params = []):JsonResponse {
    switch ($method) {
      case 'wallet_open':
        $rpcUri = $this->getRpcUri() . "/wallet/open";
        break;
    }

    $response = $this->httpClient->post($rpcUri, [
      'headers' => [
        'X-API-KEY' => $this->apiKey,
      ],
      RequestOptions::JSON => $params,
    ]);

    return new JsonResponse($response);
  }

  /**
   * Get wallet-api uri.
   *
   * @return string
   *   The wallet-api host and port.
   */
  public function getRpcUri(): string {
    return "$this->host:$this->port";
  }

}
