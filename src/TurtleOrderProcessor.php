<?php

namespace Drupal\commerce_turtlecoin;

use Drupal\commerce_currency_resolver\CurrencyHelper;
use Drupal\commerce_currency_resolver\CurrentCurrency;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

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
    $this->routeMatch = $route_match;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    ddl($order->id());

    if ($total = $order->getTotalPrice() && !$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

      // TODO: Fix for XTR currency code.
      if ($payment_gateway_plugin_id === 'turtlepay_payment_gateway' && $total->getCurrencyCode() !== 'XTR') {
        // Get order items.
        $items = $order->getItems();

        // Loop trough all order items and find ones without PurchasableEntity
        // They need to automatically converted.
        //foreach ($items as $item) {
          //$price = $item->getUnitPrice();
          /** @var \Drupal\commerce_order\Entity\OrderItem $item */
          //if (/*!$item->hasPurchasedEntity()*/$price->getCurrencyCode() !== 'XTR') {
            // Auto calculate price.
            //$item->setUnitPrice(CurrencyHelper::priceConversion($price, 'XTR'));
          //}
        //}

        // Get new total price.
        //$order = $order->recalculateTotalPrice();
        //ddl($order->getTotalPrice()->getCurrencyCode());

        // Refresh order on load. Shipping fix. Probably all other potential
        // unlocked adjustments which are not set correctly.
        //$order->setRefreshState(Order::REFRESH_ON_LOAD);

        // Save order.
        //$order->save();
      }
    }
  }

}
