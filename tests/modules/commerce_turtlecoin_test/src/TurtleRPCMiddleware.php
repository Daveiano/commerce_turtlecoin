<?php

namespace Drupal\commerce_turtlecoin_test;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle middleware for the OMDb API.
 */
class TurtleRPCMiddleware {

  /**
   * Invoked method that returns a promise.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();

        // API requests to OMDb.
        if ($uri->getPath() === '/json_rpc') {
          return $this->createPromise($request);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options);
      };
    };
  }

  /**
   * Creates a promise for the OMDb request.
   *
   * @param RequestInterface $request
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   */
  protected function createPromise(RequestInterface $request) {
    $data = Json::decode($request->getBody()->getContents());

    $response_data = [];

    switch ($data['method']) {
      case 'createIntegratedAddress':
        $response_data = [
          "id" => 1,
          "jsonrpc" => "2.0",
          "result" => [
            "integratedAddress" => "TRTLuxiNXhy96RNDkv2jx29jL7GdTWYBmA4r7K8KRpDWA4hJJnTZEgFHFzxqvmBLtz94oF4uPokQdHbV9j2g7S6LA4hKPvjZEFS2CiAj6DL8isYELmTec8Z9BK56oL1KMhjMRSMyfwYaogKg17hQKC23CHPBcHqrHHGzdRYUk3HGqkMwXbHg3BoCpXD",
          ],
        ];
        break;

      case 'getStatus':
        $response_data = [
          "id" => 1,
          "jsonrpc" => "2.0",
          "result" => [
            "blockCount" => "455956",
            "knownBlockCount" => "455956",
            "lastBlockHash" => "ABC",
            "peerCount" => 8,
          ],
        ];
        break;
    }

    $response = new Response(200, [], Json::encode($response_data));
    return new FulfilledPromise($response);
  }

}
