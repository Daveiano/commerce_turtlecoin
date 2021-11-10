<?php

namespace Drupal\commerce_turtlecoin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use TurtleCoin\Http\JsonResponse;

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
   * @todo Init with settings.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client.
   */
  public function __construct(Client $client) {
    $this->httpClient = $client;

    // @todo dynamic.
    // @todo Get daemon data from Settings and fallback to default if not set.
    $this->config = [
      "daemonHost" => "127.0.0.1",
      "daemonPort" => 11898,
      "filename" => "mywallet.wallet",
      "password" => "!3756861dBa",
    ];

    // @todo Get from payment gateway config.
    $this->host = "127.0.0.1";
    $this->port = 8070;
    $this->apiKey = "password";

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

  public function status() {
    return $this->rpcGet('status');
  }

  public function createIntegratedAddress(string $address, string $paymentId) {
    return $this->rpcGet('createIntegratedAddress', [
      'address' => $address,
      'paymentId' => $paymentId,
    ]);
  }

  public function getTransactions(string $address, int $start_height) {
    return $this->rpcGet('getTransactions', [
      'address' => $address,
      'startHeight' => $start_height,
    ]);
  }

  public function rpcGet(string $method, array $params = []):JsonResponse {
    switch ($method) {
      case 'status':
        $rpcUri = $this->getRpcUri() . "/status";
        break;

      case 'createIntegratedAddress':
        $rpcUri = $this->getRpcUri() . "/addresses/" . $params['address'] . '/' . $params['paymentId'];
        break;

      case 'getTransactions':
        $rpcUri = $this->getRpcUri() . "/address/" . $params['address'] . '/' . $params['startHeight'];
        break;
    }

    $response = $this->httpClient->get($rpcUri, [
      'headers' => [
        'X-API-KEY' => $this->apiKey,
      ],
    ]);

    return new JsonResponse($response);
  }

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

  public function getRpcUri() {
    return "$this->host:$this->port";
  }

}
