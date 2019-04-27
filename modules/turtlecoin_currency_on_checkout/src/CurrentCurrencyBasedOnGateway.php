<?php

namespace Drupal\turtlecoin_currency_on_checkout;

use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\commerce_turtlecoin\Controller\TurtleCoinBaseController;

/**
 * Checks if order currency should be TRTL.
 *
 * Decision is made based on payment gateway.
 */
class CurrentCurrencyBasedOnGateway implements CurrentCurrencyInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    $currency = NULL;

    /* @var CurrentStoreInterface $cs */
    $cs = \Drupal::service('commerce_store.current_store');
    /* @var CartProviderInterface $cpi */
    $cpi = \Drupal::service('commerce_cart.cart_provider');
    $order = $cpi->getCart('default', $cs->getStore());

    if ($order && !$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

      if (in_array($payment_gateway_plugin_id, TurtleCoinBaseController::TURTLE_PAYMENT_GATEWAYS)) {
        $currency = TurtleCoinBaseController::TURTLE_CURRENCY_CODE;
      }
    }

    return $currency;
  }

}
