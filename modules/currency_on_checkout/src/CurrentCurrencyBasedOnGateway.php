<?php

namespace Drupal\commerce_turtlecoin_currency_on_checkout;

use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\commerce_turtlecoin\TurtleCoinService;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Static cache of resolved currency. One per request.
   *
   * @var string
   */
  protected $currency;

  /**
   * The Turtle Coin service.
   *
   * @var \Drupal\commerce_turtlecoin\TurtleCoinService
   */
  protected $turtleCoinService;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, AccountProxyInterface $current_user, RouteMatchInterface $route_match, TurtleCoinService $turtle_coin_service) {
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->currency = new \SplObjectStorage();
    $this->turtleCoinService = $turtle_coin_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    $request = $this->requestStack->getCurrentRequest();

    if (!$this->currency->contains($request)) {
      $currency = NULL;
      // @todo Use dependency injection. But: if we inject the current_store
      // and the cart provider, following message appears:
      // Circular reference detected for service "commerce_order.order_refresh",
      // path: "commerce_order.order_refresh ->
      // commerce_price.chain_price_resolver ->
      // commerce_turtlecoin_currency_on_checkout.commerce_turtlecoin_price_resolver ->
      // commerce_turtlecoin_currency_on_checkout.current_currency_based_on_gateway ->
      // commerce_cart.cart_provider".
      /* @var \Drupal\commerce_store\CurrentStoreInterface $cs */
      $cs = \Drupal::service('commerce_store.current_store');
      /* @var \Drupal\commerce_cart\CartProviderInterface $cpi */
      $cpi = \Drupal::service('commerce_cart.cart_provider');
      $order = $cpi->getCart('default', $cs->getStore(), $this->currentUser->getAccount());

      $product = $this->routeMatch->getParameter('commerce_product');

      if ($order && $product && !$order->get('payment_gateway')->isEmpty() && $order->getTotalPrice()->getCurrencyCode() === $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO) {
        $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

        if (in_array($payment_gateway_plugin_id, $this->turtleCoinService::TURTLE_PAYMENT_GATEWAYS)) {
          $currency = $this->turtleCoinService::TURTLE_CURRENCY_CODE_PSEUDO;
          $this->currency[$request] = $currency;

          return $currency;
        }
      }
    }
  }

}
