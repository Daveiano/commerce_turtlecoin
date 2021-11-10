<?php

namespace Drupal\commerce_turtlecoin_test;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle middleware for the wallet-api tests.
 */
class TurtleRPCMiddleware {

  /**
   * Invoked method that returns a promise.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();

        // Wallet-api requests.
        if ($uri->getHost() === '127.0.0.1' && $uri->getPort() === 8070) {
          return $this->createPromise($request);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options);
      };
    };
  }

  /**
   * Creates a promise for the wallet-api request.
   *
   * @param RequestInterface $request
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   */
  protected function createPromise(RequestInterface $request) {
    $response_data = [];

    switch ($request->getUri()->getPath()) {
      case strpos($request->getUri()->getPath(), '/addresses/TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM/') !== FALSE:
        $response_data = [
          "integratedAddress" => "TRTLuxiNXhy96RNDkv2jx29jL7GdTWYBmA4r7K8KRpDWA4hJJnTZEgFHFzxqvmBLtz94oF4uPokQdHbV9j2g7S6LA4hKPvjZEFS2CiAj6DL8isYELmTec8Z9BK56oL1KMhjMRSMyfwYaogKg17hQKC23CHPBcHqrHHGzdRYUk3HGqkMwXbHg3BoCpXD",
        ];
        break;

      // @see \Drupal\Tests\commerce_turtlecoin\Kernel\TurtlecoinPaymentProcessingTest::testPaymentComplete
      case '/transactions/address/TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM/1000':
        $response_data = [
          "transactions" => [
            [
              "transfers" => [
                  [
                    "address" => "TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM",
                    "amount" => 513480,
                  ],
              ],
              "hash" => "f470547c88e209052a2e97df5f6ea9be2fbf2973605abb0f2dff922f33a8905c",
              "fee" => 5000,
              "blockHeight" => 4096780,
              "timestamp" => 1636503641,
              "paymentID" => "0aa63a0044724e208fd15a55c44689b98fd7063257f66bedf781fd826f514f5e",
              "unlockTime" => 0,
              "isCoinbaseTransaction" => FALSE,
            ],
          ],
        ];
        break;

      // @see \Drupal\Tests\commerce_turtlecoin\Kernel\TurtlecoinPaymentProcessingTest::testPaymentVoid
      case '/transactions/address/TRTLuxCSbSf4jFwi9rG8k4Gxd5H4wZ5NKPq4xmX72TpXRrAf4V6Ykr81MVYSaqVMdkA5qYkrrjZFZGNR8XPK8WqsSfcfU4RHhVM/1500':
        $response_data = [
          "transactions" => [],
        ];
        break;

      case '/status':
        $response_data = [
          "networkBlockCount" => "455956",
          "localDaemonBlockCount" => "455956",
          "peerCount" => 8,
        ];
        break;
    }

    $response = new Response(200, [], Json::encode($response_data));
    return new FulfilledPromise($response);
  }

}
