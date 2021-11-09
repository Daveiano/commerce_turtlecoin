<?php

namespace Drupal\Tests\commerce_turtlecoin\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group commerce_turtlecoin
 */
class TurtleCoinGatewayAdminTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_turtlecoin',
    'commerce_turtlecoin_test',
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
  protected function setUp(): void {
    parent::setUp();

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
  public function testTurtleCoinGatewayAdd() {
    $this->drupalGet('admin/commerce/config/payment-gateways/add');

    $this->getSession()->getPage()->fillField('edit-label', 'TurtleCoinManualAdded');
    $this->getSession()->getPage()->selectFieldOption('edit-plugin-turtlecoin-payment-gateway', 'turtlecoin_payment_gateway', FALSE);

    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('Display name', 'TurtleCoin');
    $this->getSession()->getPage()->selectFieldOption('Debug', 'debug', FALSE);
    $this->getSession()->getPage()->fillField('TurtleCoin address', 'TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1');
    $this->getSession()->getPage()->selectFieldOption('Enabled', TRUE, FALSE);

    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Saved the TurtleCoinManualAdded payment gateway.');
    $this->assertSession()->pageTextContains('You are connected!');
    $this->assertSession()->pageTextContains('Network block count: 455956');
    $this->assertSession()->pageTextContains('Your nodes block count: 455956');
    $this->assertSession()->pageTextContains('Connected peers: 8');

    $payment_gateway = PaymentGateway::load('turtlecoinmanualadded');
    $this->assertEquals('turtlecoinmanualadded', $payment_gateway->id());
    $this->assertEquals('TurtleCoinManualAdded', $payment_gateway->label());
    $this->assertEquals('turtlecoin_payment_gateway', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());

    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('debug', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('TRTLv211SzUJigmnbqM5mYbv8asQvJEzBBWUdBNw2GSXMpDu3m2Csf63j2dHRSkCbDGMb24a4wTjc82JofqjgTao9zjd7ZZnhA1', $configuration['turtlecoin_address_store']);

    // Check default values from the gateway.
    $this->assertEquals('http://localhost', $configuration['wallet_api_host']);
    $this->assertEquals('8070', $configuration['wallet_api_port']);
    $this->assertEquals('password', $configuration['wallet_api_password']);
    $this->assertEquals(3600, $configuration['wait_for_transactions_time']);
  }

}
