<?php

namespace Drupal\Tests\commerce_turtlecoin\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group commerce_turtlecoin
 */
class TurtlePayGatewayTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
  ];

  /**
   * The theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->store->set('default_currency', 'TRT');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 1000,
        'currency_code' => 'TRT',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer commerce_payment_gateway',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test the creation of the TurtleCoin payment gateway.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testTurtlePayGatewayAdd() {
    $this->drupalGet('admin/commerce/config/payment-gateways/add');

    $this->getSession()->getPage()->fillField('edit-label', 'TurtlePay2');
    $this->getSession()->getPage()->selectFieldOption('edit-plugin-turtlepay-payment-gateway', 'turtlepay_payment_gateway', FALSE);

    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('Display name', 'TurtlePay2');
    $this->getSession()->getPage()->selectFieldOption('Debug', 'debug', FALSE);
    $this->getSession()->getPage()->fillField('TurtleCoin address', 'TRTLv1h8Ftb1sKNVFrXq7jYt4RsZVJcdVfda58UW5a7vLPDQP7dEivp579PHUosEECZLVo82FpHWvee4Xzy3b1ryhtj67XJG9Le');
    $this->getSession()->getPage()->fillField('TurtleCoin private View Key', '67ff5aff44f8bf3487006cf53ebb6ca7137fdd234b9194d5ee9fb9d3b729920f');
    $this->getSession()->getPage()->selectFieldOption('Enabled', TRUE, FALSE);

    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Saved the TurtlePay2 payment gateway.');

    $payment_gateway = PaymentGateway::load('turtlepay2');
    $this->assertEquals('turtlepay2', $payment_gateway->id());
    $this->assertEquals('TurtlePay2', $payment_gateway->label());
    $this->assertEquals('turtlepay_payment_gateway', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());

    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('debug', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('TRTLv1h8Ftb1sKNVFrXq7jYt4RsZVJcdVfda58UW5a7vLPDQP7dEivp579PHUosEECZLVo82FpHWvee4Xzy3b1ryhtj67XJG9Le', $configuration['turtlecoin_address_store']);
    $this->assertEquals('67ff5aff44f8bf3487006cf53ebb6ca7137fdd234b9194d5ee9fb9d3b729920f', $configuration['turtlecoin_private_view_key']);

    // Check default values from the gateway.
    $this->assertEquals('https://yourdomain.com', $configuration['turtlepay_callback_host']);
  }

  /**
   * @todo Import payment gateway via configs in module.
   * @todo Could be a functional test?
   */
  public function testTurtlePayCheckout() {
    // Add to cart.
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('My product added to your cart.');

    // Go to cart.
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->assertSession()->pageTextContains('Shopping cart');
    $this->submitForm([], 'Checkout');

    // Checkout.
    $this->assertSession()->pageTextContains('Order information');
    $this->submitForm([], 'Continue to review');

    // Review.
    $this->assertSession()->pageTextContains('Review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('TurtlePay');
    $this->submitForm([], 'Pay and complete purchase');
  }

}
