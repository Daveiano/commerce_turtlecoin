<?php

namespace Drupal\commerce_turtlecoin_currency_on_checkout\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\commerce_exchanger\ExchangerCalculatorInterface;

/**
 * Returns a price and currency depending of language or country.
 *
 * @see \Drupal\commerce_currency_resolver\Resolver\CommerceCurrencyResolver.
 */
class CommerceTurtleCoinResolver implements PriceResolverInterface {

  /**
   * The current currency.
   *
   * @var \Drupal\commerce_currency_resolver\CurrentCurrencyInterface
   */
  protected $currentCurrency;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Price Calculator.
   *
   * @var \Drupal\commerce_exchanger\ExchangerCalculatorInterface
   */
  protected $calculator;

  /**
   * The Turtle Coin service.
   *
   * @var \Drupal\commerce_turtlecoin\TurtleCoinService
   */
  protected $turtleCoinService;

  /**
   * Constructs a new CommerceCurrencyResolver object.
   *
   * @param \Drupal\commerce_currency_resolver\CurrentCurrencyInterface $current_currency
   *   The currency manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\commerce_exchanger\ExchangerCalculatorInterface $calculator
   *   Price Calculator.
   * @param \Drupal\commerce_turtlecoin\TurtleCoinService $turtle_coin_service
   *   Turtle Coin service.
   */
  public function __construct(CurrentCurrencyInterface $current_currency, ConfigFactoryInterface $config_factory, ExchangerCalculatorInterface $calculator, TurtleCoinService $turtle_coin_service) {
    $this->currentCurrency = $current_currency;
    $this->configFactory = $config_factory;
    $this->calculator = $calculator;
    $this->turtleCoinService = $turtle_coin_service;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Default price.
    $price = NULL;

    if ($this->currentCurrency->getCurrency() === $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO) {
      // Get field from context.
      $field_name = $context->getData('field_name', 'price');

      // @see \Drupal\commerce_price\Resolver\DefaultPriceResolver
      if ($field_name === 'price') {
        $price = $entity->getPrice();
      }
      elseif ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $price = $entity->get($field_name)->first()->toPrice();
      }

      // If we have price.
      if ($price && $price->getCurrencyCode() !== $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO) {
        // Get how price should be calculated.
        $currency_source = $this->configFactory->get('commerce_currency_resolver.settings')->get('currency_source');

        // Auto-calculate price by default. Fallback for all cases regardless
        // of chosen currency source mode.
        $resolved_price = $this->calculator->priceConversion($price, $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO);

        // Specific cases for field and combo. Even we had autocalculated
        // price, in combo mode we could have field with price.
        if ($currency_source === 'combo' || $currency_source === 'field') {

          // Backward compatibility for older version, and inital setup
          // that default price fields are mapped to field_price_currency_code
          // instead to price_currency_code.
          if ($field_name === 'price') {
            $field_name = 'field_price';
          }

          $resolved_field = $field_name . '_' . strtolower($this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO);

          // Check if we have field.
          if ($entity->hasField($resolved_field) && !$entity->get($resolved_field)->isEmpty()) {
            $resolved_price = $entity->get($resolved_field)->first()->toPrice();
          }
        }

        return $resolved_price;
      }

      // Return price if conversion is not needed.
      return $price;
    }

    return $price;
  }

}
