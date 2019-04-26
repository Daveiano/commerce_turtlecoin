<?php

namespace Drupal\commerce_turtlecoin\EventSubscriber;

use Drupal\commerce_currency_resolver\CommerceCurrencyResolversRefreshTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\OrderRefreshInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Checking for mismatch in currencies on order.
 *
 * @package Drupal\commerce_currency_resolver\EventSubscriber
 */
class TurtleCoinCurrencyOrderRefresh implements EventSubscriberInterface {

  use CommerceCurrencyResolversRefreshTrait;

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OrderRefreshInterface $order_refresh, AccountInterface $account, RouteMatchInterface $route_match) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->orderRefresh = $order_refresh;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.commerce_order.update' => 'checkCurrency',
    ];
    return $events;
  }

  /**
   * Check for misplace in currency. Refresh order if necessary.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function checkCurrency(OrderEvent $event) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();

    // Get order total currency.
    $order_total = $order->getTotalPrice();
    if ($order_total && !$order->get('payment_gateway')->isEmpty()) {

      $order_currency = $order_total->getCurrencyCode();
      $order_payment = $order->payment_gateway->entity->getPluginId();
      $currency_resolver_skip = $order->getData('currency_resolver_skip');
      $commerce_turtlecoin_skipped = $order->getData('commerce_turtlecoin_skipped');

      // Compare order total and main resolved currency.
      // Refresh order if they are different. We need then alter total price.
      // This will trigger order processor which will handle
      // correction of total order price and currency.
      // TODO: shouldCurrencyRefresh on own conditions.
      if (($order_currency !== 'XTR') && ($order_payment === 'turtlepay_payment_gateway')/* && ($this->shouldCurrencyRefresh($order))*/) {
        // Check if we can refresh order.
        $this->orderRefresh->refresh($order);
        $order->save();
      }
      // TODO: Add Turtle RPC.
      // TODO: What should be the condition here? Resolved currency from currency_resolver?
      // Resolved currency from currency_resolver && ($currency_resolver_skip && $commerce_turtlecoin_skipped) && shouldrefresh
      elseif ($currency_resolver_skip && $commerce_turtlecoin_skipped && ($order_payment !== 'turtlepay_payment_gateway') && ($order_currency === 'XTR')) {
        $this->orderRefresh->refresh($order);
        $order->save();
      }
    }
  }

}
