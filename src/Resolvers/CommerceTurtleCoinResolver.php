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
class CommerceTurtleCoinResolver extends CommerceCurrencyResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   *
   * TODO: Does not work :( Implement TRTL conversion based on used payment
   * gateway for current order.
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    ddl($entity);
    parent::resolve($entity, $quantity, $context);
  }

}
