<?php

namespace Drupal\turtlecoin_currency_on_checkout\EventSubscriber;

use Drupal\turtlecoin_currency_on_checkout\CommerceTurtleCoinResolversRefreshTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\OrderRefreshInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Checking for mismatch in currencies on order.
 *
 * @package Drupal\commerce_currency_resolver\EventSubscriber
 */
class TurtleCoinCurrencyOrderRefresh implements EventSubscriberInterface {

  use CommerceTurtleCoinResolversRefreshTrait;

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
      $turtlecoin_payment_gateways = TurtleCoinBaseController::TURTLE_PAYMENT_GATEWAYS;
      $turtlecoin_currency_code = TurtleCoinBaseController::TURTLE_CURRENCY_CODE;

      // Compare order total and main resolved currency.
      // Refresh order if they are different. We need then alter total price.
      // This will trigger order processor which will handle
      // correction of total order price and currency.
      if (($order_currency !== $turtlecoin_currency_code) && in_array($order_payment, $turtlecoin_payment_gateways) && $this->shouldCurrencyRefresh($order)) {
        // Check if we can refresh order.
        $this->orderRefresh->refresh($order);
        $order->save();
      }
      elseif ($this->turtleCoinSetSkip($order) && !in_array($order_payment, $turtlecoin_payment_gateways) && ($order_currency === $turtlecoin_currency_code) && $this->shouldCurrencyRefresh($order)) {
        $this->orderRefresh->refresh($order);
        $order->save();
      }
    }
  }

}
