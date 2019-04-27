<?php

namespace Drupal\turtlecoin_currency_on_checkout\EventSubscriber;

use Drupal\commerce_currency_resolver\CurrencyHelper;
use Drupal\commerce_currency_resolver\ExchangeRateEventSubscriberBase;
use Drupal\Component\Serialization\Json;

/**
 * Class ExchangeRateCryptoCompare.
 */
class ExchangeRateCryptoCompare extends ExchangeRateEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function apiUrl() {
    return 'https://min-api.cryptocompare.com/data';
  }

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'exchange_rate_crypto_compare';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalData($base_currency = NULL) {
    $data = NULL;

    $enabled_currencies = CurrencyHelper::getEnabledCurrency();

    // Prepare client.
    $url = self::apiUrl() . '/price';
    $options = [
      'query' => [
        // TODO: Fix for the provisional XTR currency code.
        'tsyms' => str_replace('XTR', 'TRTL', implode(',', array_keys($enabled_currencies))),
        'api_key' => self::apiKey(),
      ],
    ];

    if (!empty($base_currency)) {
      $options['query']['fsym'] = $base_currency;
    }

    $response = $this->apiClient('GET', $url, $options);

    if ($response) {
      $exchange_rates = Json::decode($response);
      $data['base'] = $base_currency;

      foreach ($exchange_rates as $currency => $exchange_rate) {
        // TODO: Fix for the provisional XTR currency code.
        if ($currency === 'TRTL') {
          $currency = 'XTR';
        }

        $data['rates'][$currency] = $exchange_rate;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function processCurrencies() {
    $exchange_rates = [];
    $currency_default = \Drupal::config('commerce_currency_resolver.settings')
      ->get('currency_default');

    $data = $this->getExternalData($currency_default);

    if ($data) {
      // Currency and rate array. Data should be in format:
      // $data[$code] = $rate.
      $exchange_rates = $this->crossSyncCalculate($data['base'], $data['rates']);

    }

    return $exchange_rates;
  }

}
