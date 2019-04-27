<?php

namespace Drupal\turtlecoin_currency_on_checkout;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Handle access where currency resolver can refresh order.
 *
 * @see Drupal\commerce_currency_resolver\CurrencyResolverResolversRefreshTrait
 */
trait CommerceTurtleCoinResolversRefreshTrait {

  /**
   * Check admin route.
   */
  public function checkAdminRoute() {
    // Get current route. Skip admin path.
    return \Drupal::service('router.admin_context')->isAdminRoute($this->routeMatch->getRouteObject());
  }

  /**
   * Check if order belongs to current user.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order object.
   *
   * @return bool
   *   Return true if this is not order owner.
   */
  public function checkOrderOwner(OrderInterface $order) {
    return (int) $order->getCustomerId() !== (int) $this->account->id();
  }

  /**
   * Check if order is in draft status.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order object.
   *
   * @return bool
   *   Return true if order is not in draft state.
   */
  public function checkOrderStatus(OrderInterface $order) {
    // Only draft orders should be recalculated.
    return $order->getState()->value !== 'draft';
  }

  /**
   * Check if currency_resolver_skip was set from Turtle.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order object..
   *
   * @return bool
   *   Return true if currency_resolver_skip was set from turtle.
   */
  public function turtleCoinSetSkip(OrderInterface $order) {
    $currency_resolver_skip = $order->getData('currency_resolver_skip');
    $commerce_turtlecoin_skipped = $order->getData('commerce_turtlecoin_skipped');

    if ($currency_resolver_skip && $commerce_turtlecoin_skipped) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get refresh state based on path.
   *
   * @return bool
   *   Return true or false.
   */
  public function shouldCurrencyRefresh(OrderInterface $order) {

    $currency_resolver_skip = $order->getData('currency_resolver_skip');
    $commerce_turtlecoin_skipped = $order->getData('commerce_turtlecoin_skipped');

    // If order have specific flag set, skip refreshing currency.
    if ($currency_resolver_skip  && !$commerce_turtlecoin_skipped) {
      return FALSE;
    }

    // Do not trigger currency refresh in cli - drush, cron, etc.
    // If we load order in cli, we don't want to manipulate order
    // with currency refresh.
    if (PHP_SAPI === 'cli') {
      return FALSE;
    }

    // Not owner of order.
    if ($this->checkOrderOwner($order)) {
      return FALSE;
    }

    // Order is not in draft status.
    if ($this->checkOrderStatus($order)) {
      return FALSE;
    }

    // Admin route.
    if ($this->checkAdminRoute()) {
      return FALSE;
    }

    return TRUE;
  }

}
