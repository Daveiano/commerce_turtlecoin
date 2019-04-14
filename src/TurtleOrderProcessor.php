<?php

namespace Drupal\commerce_turtlecoin;

use Drupal\commerce_currency_resolver\CurrencyHelper;
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
   * The current currency.
   *
   * @var \Drupal\commerce_currency_resolver\CurrentCurrencyInterface
   */
  protected $currentCurrency;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RouteMatchInterface $route_match, CurrentCurrencyBasedOnGateway $current_currency) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->currentCurrency = $current_currency;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    //ddl($this->currentCurrency->getCurrency());
    //ddl($this->currentCurrency->getCurrency());
    if ($this->currentCurrency->getCurrency() === 'XTR') {
      $total = $order->getTotalPrice();


      if ($total->getCurrencyCode() !== 'XTR') {
        // Get order items.
        $items = $order->getItems();

        // Loop trough all order items and find ones without PurchasableEntity
        // They need to automatically converted.
        foreach ($items as $item) {
          // @var \Drupal\commerce_order\Entity\OrderItem $item
          if (!$item->hasPurchasedEntity()) {
            $price = $item->getUnitPrice();
            // Auto calculate price.
            ddl('orderitem frm order processor');
            ddl($item->getTitle());
            $item->setUnitPrice(CurrencyHelper::priceConversion($price, 'XTR'));
          }
        }

        // Get new total price.
        $order->setData('currency_resolver_skip', TRUE);
        $order->recalculateTotalPrice();

        //ddl($order->getTotalPrice());

        // Refresh order on load. Shipping fix. Probably all other potential
        // unlocked adjustments which are not set correctly.
        $order->setRefreshState(Order::REFRESH_ON_SAVE);

        // Save order.
        $order->save();
      }
    }
    elseif ($order->getData('currency_resolver_skip')) {
      // TODO: Add condition.
      //$order->setData('currency_resolver_skip', FALSE);
      //$order->save();
    }
  }

}
