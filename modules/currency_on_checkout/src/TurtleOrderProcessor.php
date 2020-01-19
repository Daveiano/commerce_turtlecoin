<?php

namespace Drupal\commerce_turtlecoin_currency_on_checkout;

use Drupal\commerce_exchanger\ExchangerCalculatorInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class TurtleOrderProcessor.
 *
 * @package Drupal\commerce_turtlecoin_currency_on_checkout
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
   * Price exchanger service.
   *
   * @var \Drupal\commerce_exchanger\ExchangerCalculatorInterface
   */
  protected $priceExchanger;

  /**
   * The Turtle Coin service.
   *
   * @var \Drupal\commerce_turtlecoin\TurtleCoinService
   */
  protected $turtleCoinService;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RouteMatchInterface $route_match, ExchangerCalculatorInterface $price_exchanger, TurtleCoinService $turtle_coin_service) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->priceExchanger = $price_exchanger;
    $this->turtleCoinService = $turtle_coin_service;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order && !$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

      if (in_array($payment_gateway_plugin_id, $this->turtleCoinService::TURTLE_PAYMENT_GATEWAYS)) {
        $total = $order->getTotalPrice();

        if ($total && $total->getCurrencyCode() !== $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO) {

          // Get order items.
          $items = $order->getItems();

          // Loop trough all order items and find ones without PurchasableEntity
          // They need to automatically converted.
          foreach ($items as $item) {
            // @var \Drupal\commerce_order\Entity\OrderItem $item
            $price = $item->getUnitPrice();
            // Auto calculate price.
            $item->setUnitPrice($this->priceExchanger->priceConversion($price, $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO));
          }

          $order = $order->set('order_items', []);
          $order = $order->set('order_items', $items);

          // Handle shipping module.
          if (\Drupal::service('module_handler')->moduleExists('commerce_shipping')) {
            if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {

              // Get order shipments.
              $shipments = $order->shipments->referencedEntities();

              $update_shipments = $this->processShipments($shipments, $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO);

              if ($update_shipments) {
                $order = $order->set('shipments', $shipments);
              }
            }
          }

          $adjustments = $order->getAdjustments();
          $new_adjustments = [];
          $reset_adjustments = FALSE;

          foreach ($adjustments as $adjustment) {
            assert($adjustment instanceof Adjustment);

            $adjustment_currency = $adjustment->getAmount()->getCurrencyCode();

            if ($adjustment_currency !== $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO) {
              $reset_adjustments = TRUE;
              $adjustment_amount = $adjustment->getAmount();
              $values = $adjustment->toArray();
              // Auto calculate price.
              $values['amount'] = $this->priceExchanger->priceConversion($adjustment_amount, $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO);
              $new_adjustment = new Adjustment($values);
              $new_adjustments[] = $new_adjustment;
            }
          }

          // We have custom adjustments which need to be recalculated.
          if ($reset_adjustments) {
            // We need clear adjustments like that while using
            // $order->removeAdjustment() will trigger recalculateTotalPrice()
            // which will break everything, while currencies are different.
            $order = $order->set('adjustments', []);

            foreach ($new_adjustments as $new_adjustment) {
              $order = $order->addAdjustment($new_adjustment);
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
          //$order->setRefreshState(Order::REFRESH_ON_LOAD);
        }
      }
      elseif ($order->getData('currency_resolver_skip') && $order->getData('commerce_turtlecoin_skipped') && !in_array($payment_gateway_plugin_id, $this->turtleCoinService::TURTLE_PAYMENT_GATEWAYS)) {
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

              // We have get new rate. But again duo to fact that we don't
              // know if user is using multicurrency conditions or not,
              // convert price just in case if is different currency.
              if ($shipment->getAmount()->getCurrencyCode() !== $resolved_currency) {
                $shipment->setAmount($this->priceExchanger->priceConversion($shipment->getAmount(), $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO));
              }

              $shipments[$key] = $shipment;
              $updateShipping = $shipments;
            }

            // We haven't found anything, automatically convert price.
            else {
              $shipment->setAmount($this->priceExchanger->priceConversion($shipment->getAmount(), $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO));
            }
          }
        }
      }
    }

    return $updateShipping;
  }

}
