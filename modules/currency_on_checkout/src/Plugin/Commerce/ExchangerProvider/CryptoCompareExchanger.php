<?php

namespace Drupal\commerce_turtlecoin_currency_on_checkout\Plugin\Commerce\ExchangerProvider;

use Drupal\commerce_currency_resolver\CurrencyHelper;
use Drupal\commerce_exchanger\Plugin\Commerce\ExchangerProvider\ExchangerProviderRemoteBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Http\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    // TODO: Fix for the provisional XTR currency code.
    if ($base_currency === 'XTR') {
      $base_currency = 'TRTL';
    }

    $currencies = \Drupal::service('entity_type.manager')->getStorage('commerce_currency')->loadMultiple();

    // Prepare client.
    $options = [
      'query' => [
        // TODO: Fix for the provisional XTR currency code.
        'tsyms' => str_replace('XTR', 'TRTL', implode(',', array_keys($currencies))),
        'api_key' => $this->getApiKey(),
      ],
    ];

    if (!empty($base_currency)) {
      $options['query']['fsyms'] = $base_currency;
    }
    else {
      // TODO: Fix for the provisional XTR currency code.
      $options['query']['fsyms'] = str_replace('XTR', 'TRTL', implode(',', array_keys($currencies)));
    }

    $response = $this->apiClient($options);

    if ($response) {
      $exchange_rates = Json::decode($response);

      $data['base'] = $base_currency;
      $data['rates'] = [];

      foreach ($exchange_rates[$base_currency] as $currency => $exchange_rate) {
        // TODO: Fix for the provisional XTR currency code.
        if ($currency === 'TRTL') {
          $currency = 'XTR';
        }

        $data['rates'][$currency] = $exchange_rate;
      }
    }

    // TODO: Fix for the provisional XTR currency code.
    if ($base_currency === 'TRTL') {
      $data['base'] = 'XTR';
    }

    return $data;
  }

}
