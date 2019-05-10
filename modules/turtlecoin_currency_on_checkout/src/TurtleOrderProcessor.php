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
    ddl('order processor');
    if ($order && !$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();
      ddl('order processor works');

      if (in_array($payment_gateway_plugin_id, TurtleCoinBaseController::TURTLE_PAYMENT_GATEWAYS)) {
        $total = $order->getTotalPrice();

        // Handle shipping module.
        if (\Drupal::service('module_handler')->moduleExists('commerce_shipping')) {
          ddl('shippping process');
          if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {

            // Get order shipments.
            $shipments = $order->shipments->referencedEntities();

            $update_shipments = $this->processShipments($shipments, TurtleCoinBaseController::TURTLE_CURRENCY_CODE);

            if ($update_shipments) {
              $order->set('shipments', $shipments);
            }
          }
        }

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

          // Set order data so currency_resolver knows it should skip this
          // order.
          $order->setData('currency_resolver_skip', TRUE);
          $order->setData('commerce_turtlecoin_skipped', TRUE);

          // Get new total price.
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

  /**
   * Handle shipments on currency change.
   *
   * @param \Drupal\commerce_shipping\Entity\Shipment[] $shipments
   *   List of shipments attached to the order.
   * @param string $resolved_currency
   *   Currency code.
   *
   * @return bool|\Drupal\commerce_shipping\Entity\Shipment[]
   *   FALSE if is auto-calculated, and shipments if they need to be updated.
   */
  protected function processShipments(array $shipments, $resolved_currency) {

    $updateShipping = FALSE;

    foreach ($shipments as $key => $shipment) {
      if ($amount = $shipment->getAmount()) {
        if ($amount->getCurrencyCode() !== $resolved_currency) {
          // Recalculate rates.
          if ($shipment->getShippingMethod()) {

            // Get rates. User can have conditions based on Currency,
            // or they can use multicurrency addon implementation on shipment.
            $rates = $shipment->getShippingMethod()->getPlugin()->calculateRates($shipment);

            // If we have found match, update with new rate.
            if (!empty($rates)) {
              $rate = reset($rates);
              $shipment->getShippingMethod()->getPlugin()->selectRate($shipment, $rate);

              // We have get new rate. But again due to fact that we don't
              // know if user is using multicurrency conditions or not,
              // convert price just in case if is different currency.
              if ($shipment->getAmount()->getCurrencyCode() !== $resolved_currency) {
                $shipment->setAmount(CurrencyHelper::priceConversion($shipment->getAmount(), $resolved_currency));
              }

              $shipments[$key] = $shipment;
              $updateShipping = $shipments;
            }

            // We haven't found anything, automatically convert price.
            else {
              $shipment->setAmount(CurrencyHelper::priceConversion($shipment->getAmount(), $resolved_currency));
            }
          }
        }
      }
    }

    ddl($updateShipping);
    return $updateShipping;
  }

}
