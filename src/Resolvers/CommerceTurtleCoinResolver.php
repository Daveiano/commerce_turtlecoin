<?php

namespace Drupal\commerce_turtlecoin\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_currency_resolver\CommerceCurrencyResolverTrait;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_currency_resolver\CurrencyHelper;
use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\commerce_currency_resolver\Resolver\CommerceCurrencyResolver;

/**
 * Returns a price and currency depending of language or country.
 *
 * @see Drupal\commerce_currency_resolver\Resolver\CommerceCurrencyResolver.
 */
class CommerceTurtleCoinResolver implements PriceResolverInterface {

  /**
   * The current currency.
   *
   * @var \Drupal\commerce_currency_resolver\CurrentCurrencyInterface
   */
  protected $currentCurrency;

  /**
   * Constructs a new CommerceCurrencyResolver object.
   *
   * @param \Drupal\commerce_currency_resolver\CurrentCurrencyInterface $current_currency
   *   The currency manager.
   */
  public function __construct(CurrentCurrencyInterface $current_currency) {
    $this->currentCurrency = $current_currency;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Implement TRTL conversion based on used payment
   * gateway for current order.
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Default price.
    $price = NULL;

    // Get the current order.
    $order = \Drupal::routeMatch()->getParameter('commerce_order');
    if (!empty($order) && !$order->get('payment_gateway')->isEmpty()) {
      // Check for the used payment gateway, we want TRTL in case
      // of turtle_coin and turtle_pay.
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();
      ddl($payment_gateway_plugin_id);
      // Get current resolved currency.
      // TODO: $price->getCurrencyCode() or $resolved_currency.
      $resolved_currency = $this->currentCurrency->getCurrency();

      // Get field from context.
      // @see Drupal\commerce_currency_resolver\Resolver\CommerceCurrencyResolver.
      $field_name = $context->getData('field_name', 'price');

      // @see \Drupal\commerce_price\Resolver\DefaultPriceResolver
      if ($field_name === 'price') {
        $price = $entity->getPrice();
      }
      elseif ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $price = $entity->get($field_name)->first()->toPrice();
      }

      // TODO: Fix for XTR currency code.
      if ($payment_gateway_plugin_id === 'turtlepay_payment_gateway' && $price->getCurrencyCode() !== 'XTR') {
        $resolved_price = CurrencyHelper::priceConversion($price, 'XTR');

        return $resolved_price;
      }
    }

    return $price;
  }

}
