<?php

namespace Drupal\commerce_turtlecoin_currency_on_checkout\Plugin\Commerce\ExchangerProvider;

use Drupal\commerce_exchanger\Plugin\Commerce\ExchangerProvider\ExchangerProviderRemoteBase;
use Drupal\Component\Serialization\Json;

/**
 * Provides Crypto Compare Rates.
 *
 * @CommerceExchangerProvider(
 *   id = "crypto_compare",
 *   label = "Crypto Compare",
 *   display_label = "Crypto Compare",
 *   historical_rates = FALSE,
 *   enterprise = TRUE,
 *   refresh_once = FALSE,
 *   api_key= TRUE,
 * )
 */
class CryptoCompareExchanger extends ExchangerProviderRemoteBase {

  /**
   * {@inheritdoc}
   */
  public function apiUrl() {
    return 'https://min-api.cryptocompare.com/data/pricemulti';
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteData($base_currency = NULL) {
    $data = NULL;

    $currencies = \Drupal::service('entity_type.manager')->getStorage('commerce_currency')->loadMultiple();

    // Prepare client.
    $options = [
      'query' => [
        'tsyms' => implode(',', array_keys($currencies)),
        'api_key' => $this->getApiKey(),
      ],
    ];

    if (!empty($base_currency)) {
      $options['query']['fsyms'] = $base_currency;
    }
    else {
      $options['query']['fsyms'] = implode(',', array_keys($currencies));
      $base_currency = array_keys($currencies)[0];
    }

    $response = $this->apiClient($options);

    if ($response) {
      $exchange_rates = Json::decode($response);

      $data['base'] = $base_currency;
      $data['rates'] = [];

      foreach ($exchange_rates[$base_currency] as $currency => $exchange_rate) {
        $data['rates'][$currency] = $exchange_rate;
      }
    }

    return $data;
  }

}
