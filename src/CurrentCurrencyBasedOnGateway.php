<?php

namespace Drupal\commerce_turtlecoin;

use Drupal\commerce_currency_resolver\CurrentCurrencyInterface;
use Drupal\commerce_currency_resolver\CommerceCurrencyResolverTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use CommerceGuys\Addressing\Country\CountryRepository;

/**
 * Holds a reference to the currency, resolved on demand.
 */
class CurrentCurrencyBasedOnGateway implements CurrentCurrencyInterface {

  use CommerceCurrencyResolverTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Static cache of resolved currency. One per request.
   *
   * @var string
   */
  protected $currency;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->currency = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    $request = $this->requestStack->getCurrentRequest();

    if (!$this->currency->contains($request)) {
      /* @var CurrentStoreInterface $cs */
      $cs = \Drupal::service('commerce_store.current_store');
      /* @var CartProviderInterface $cpi */
      $cpi = \Drupal::service('commerce_cart.cart_provider');
      $order = $cpi->getCart('default', $cs->getStore());

      $total = $order->getTotalPrice();
      if ($total && !$order->get('payment_gateway')->isEmpty()) {
        $payment_gateway_plugin_id = $order->payment_gateway->entity->getPluginId();

        // TODO: Fix for XTR currency code.
        if ($payment_gateway_plugin_id === 'turtlepay_payment_gateway' && $total->getCurrencyCode() !== 'XTR') {
          return 'XTR';
        }
      }
    }

    return NULL;
  }

}
