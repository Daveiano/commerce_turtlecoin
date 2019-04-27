<?php

namespace Drupal\turtlecoin_currency_on_checkout;

use Drupal\commerce_currency_resolver\CurrencyHelper;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Class TurtleOrderProcessor.
 *
 * @package Drupal\commerce_turtlecoin
 */
class TurtleOrderProcessor implements OrderProcessorInterface {

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RouteMatchInterface $route_match) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->account = $account;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order && !$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

      if (in_array($payment_gateway_plugin_id, TurtleCoinBaseController::TURTLE_PAYMENT_GATEWAYS)) {
        $total = $order->getTotalPrice();

        if ($total->getCurrencyCode() !== TurtleCoinBaseController::TURTLE_CURRENCY_CODE) {
          // Get order items.
          $items = $order->getItems();

          // Loop trough all order items and find ones without PurchasableEntity
          // They need to automatically converted.
          foreach ($items as $item) {
            // @var \Drupal\commerce_order\Entity\OrderItem $item
            if (!$item->hasPurchasedEntity()) {
              $price = $item->getUnitPrice();
              // Auto calculate price.
              $item->setUnitPrice(CurrencyHelper::priceConversion($price, TurtleCoinBaseController::TURTLE_CURRENCY_CODE));
            }
          }

          // Get new total price.
          $order->setData('currency_resolver_skip', TRUE);
          $order->setData('commerce_turtlecoin_skipped', TRUE);
          $order->recalculateTotalPrice();

          // Refresh order on load. Shipping fix. Probably all other potential
          // unlocked adjustments which are not set correctly.
          $order->setRefreshState(Order::REFRESH_ON_LOAD);
        }
      }
      elseif ($order->getData('currency_resolver_skip') && $order->getData('commerce_turtlecoin_skipped') && !in_array($payment_gateway_plugin_id, TurtleCoinBaseController::TURTLE_PAYMENT_GATEWAYS)) {
        $order->setData('currency_resolver_skip', FALSE);
        $order->setData('commerce_turtlecoin_skipped', FALSE);
        $order->setRefreshState(Order::REFRESH_ON_LOAD);
      }
    }
  }

}
